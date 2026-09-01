<?php

namespace App\Services;

use App\Exceptions\CommissionDistributionException;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AffiliationService — Moteur de distribution des commissions d'affiliation
 * (Phase 4 & 8 du cahier des charges).
 *
 * RÈGLES MÉTIER APPLIQUÉES (cf. cahier des charges) :
 *  - §4/§5   : seules les lignes de commande de type "digital" ET
 *              is_affiliable=true génèrent une commission. Physique et
 *              service sont STRICTEMENT ignorés (0 commission).
 *  - §28/§29 : 3 niveaux rémunérés, taux indépendants appliqués sur le
 *              MÊME montant éligible (15% / 10% / 5%), jamais en cascade.
 *  - §37     : seuls les 3 premiers niveaux de la lignée sont rémunérés,
 *              même si l'arbre est plus profond.
 *  - §39     : le niveau 1 est l'affilié référent de la commande
 *              (Order::referring_affiliate_id) — pas nécessairement le
 *              parrain de l'acheteur, qui n'est pas forcément affilié.
 *              Les niveaux 2 et 3 remontent ensuite la chaîne de
 *              parrainage (User::parent) à partir de ce niveau 1.
 *  - §40     : les commissions sont créées au statut PENDING, jamais
 *              directement disponibles.
 *  - §27     : un même événement de paiement traité deux fois ne doit
 *              jamais générer deux commissions (idempotence).
 *  - §48/§49 : le wallet n'est jamais écrit hors transaction + ledger.
 */
class AffiliationService
{
    /**
     * Taux de commission par niveau, appliqués indépendamment sur le
     * montant éligible (§29). Total maximum distribué : 30%.
     */
    private const COMMISSION_RATES = [
        1 => 0.15,
        2 => 0.10,
        3 => 0.05,
    ];

    private const MAX_LEVELS = 3;

    /**
     * Distribue les commissions d'affiliation pour une commande payée.
     *
     * Idempotent : si des commissions ont déjà été générées pour cette
     * commande (ex: webhook PSP reçu deux fois, §27), la méthode ne fait
     * rien et retourne silencieusement.
     *
     * @throws CommissionDistributionException si la commande n'est pas payée.
     */
    public function distributeCommissions(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // On verrouille la ligne de la commande pour la durée de la
            // transaction afin d'empêcher un traitement concurrent du même
            // événement de paiement (protection anti double-commission, §27).
            /** @var Order $order */
            $order = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // 1. Vérifier que la commande est bien payée.
            if ($order->payment_status !== 'paid') {
                throw CommissionDistributionException::orderNotPaid($order->id);
            }

            // Idempotence au niveau de la commande entière : si des lignes
            // de commission existent déjà pour cette commande, on ne
            // retraite rien (§27 - Test 7 "double événement").
            $alreadyProcessed = LedgerEntry::query()
                ->where('order_id', $order->id)
                ->where('source_type', 'commission')
                ->exists();

            if ($alreadyProcessed) {
                Log::info('AffiliationService: commissions déjà distribuées, opération ignorée.', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ]);

                return;
            }

            // 3. Déterminer les parrains de niveau 1, 2 et 3.
            $affiliatesByLevel = $this->resolveEligibleAffiliates($order);

            if (empty($affiliatesByLevel)) {
                // Pas d'affilié référent sur cette commande : rien à
                // distribuer, ce n'est pas une erreur bloquante en soi
                // (toutes les commandes ne viennent pas d'un lien d'affiliation).
                Log::info('AffiliationService: aucune commande à traiter — pas d\'affilié référent.', [
                    'order_id' => $order->id,
                ]);

                return;
            }

            // 2. Parcourir les lignes de commande éligibles (digital +
            // affiliable uniquement — §4/§5) et créer les commissions.
            $eligibleItems = $order->items()
                ->where('product_type', 'digital')
                ->where('is_affiliable_snapshot', true)
                ->get();

            if ($eligibleItems->isEmpty()) {
                Log::info('AffiliationService: aucune ligne digitale affiliable sur la commande.', [
                    'order_id' => $order->id,
                ]);

                return;
            }

            foreach ($eligibleItems as $item) {
                $this->distributeCommissionsForItem($item, $affiliatesByLevel);
            }

            Log::info('AffiliationService: commissions distribuées avec succès.', [
                'order_id' => $order->id,
                'levels_credited' => array_keys($affiliatesByLevel),
                'items_processed' => $eligibleItems->count(),
            ]);
        });
    }

    /**
     * Résout la chaîne des affiliés éligibles (niveau 1 à 3) pour une
     * commande donnée.
     *
     * Niveau 1 = affilié référent de la commande (celui dont le lien a
     * généré la vente). Niveaux 2 et 3 = on remonte l'arbre de parrainage
     * (parent_id) à partir de ce niveau 1 (§35-§39).
     *
     * Un niveau dont l'affilié n'est pas "actif" (compte suspendu/banni)
     * est exclu de la distribution, sans interrompre la remontée vers les
     * niveaux suivants.
     *
     * @return array<int, User> tableau indexé par niveau (1, 2, 3)
     */
    private function resolveEligibleAffiliates(Order $order): array
    {
        $level1 = $order->referringAffiliate; // Order::referring_affiliate_id

        if (! $level1) {
            return [];
        }

        $chain = [];
        $current = $level1;

        for ($level = 1; $level <= self::MAX_LEVELS && $current; $level++) {
            if ($this->isAffiliateEligible($current)) {
                $chain[$level] = $current;
            }

            $current = $current->parent; // remonte d'un cran dans l'arbre (§37)
        }

        return $chain;
    }

    /**
     * Un affilié est éligible à recevoir une commission s'il possède un
     * identifiant affilié actif et que son compte n'est pas suspendu/banni
     * (§62 - suspension d'un affilié).
     */
    private function isAffiliateEligible(User $user): bool
    {
        return $user->isAffiliate() && $user->status === 'active';
    }

    /**
     * Calcule et enregistre les commissions des 3 niveaux pour une ligne
     * de commande digitale affiliable donnée.
     *
     * @param  array<int, User>  $affiliatesByLevel
     */
    private function distributeCommissionsForItem(OrderItem $item, array $affiliatesByLevel): void
    {
        // Montant éligible = base de calcul figée au moment de l'achat (§5).
        $eligibleAmount = (float) ($item->commission_base_amount
            ?? ($item->total_price - $item->discount_amount));

        if ($eligibleAmount <= 0) {
            return;
        }

        foreach (self::COMMISSION_RATES as $level => $rate) {
            $affiliate = $affiliatesByLevel[$level] ?? null;

            if (! $affiliate) {
                // Pas de parrain à ce niveau (arbre moins profond que 3,
                // ou affilié non éligible) : aucune commission générée,
                // ce qui est correct (§37 - niveaux 4+ jamais rémunérés,
                // et un niveau manquant/inéligible n'est simplement pas payé).
                continue;
            }

            // §29 - taux indépendants appliqués sur le MÊME montant éligible
            $amount = round($eligibleAmount * $rate, 2);

            if ($amount <= 0) {
                continue;
            }

            $this->creditPendingCommission(
                affiliate: $affiliate,
                item: $item,
                level: $level,
                amount: $amount
            );
        }
    }

    /**
     * Crée la LedgerEntry PENDING pour un affilié/niveau/ligne donnés et
     * met à jour le solde en attente (balance_pending) de son wallet.
     */
    private function creditPendingCommission(User $affiliate, OrderItem $item, int $level, float $amount): void
    {
        $wallet = $this->lockOrCreateWallet($affiliate);

        // Référence déterministe (idempotence par contrainte unique en base,
        // §47 : format lisible pour l'historique "Mes gains").
        $reference = sprintf(
            'COM-%s-%s-L%d',
            $item->order_id,
            $item->id,
            $level
        );

        // firstOrCreate : garantit qu'un doublon (ex: retraitement
        // accidentel) ne peut pas créer deux fois la même écriture, la
        // colonne `reference` étant unique en base (§27).
        $entry = LedgerEntry::query()->firstOrCreate(
            ['reference' => $reference],
            [
                'wallet_id' => $wallet->id,
                'user_id' => $affiliate->id,
                'direction' => 'credit',
                'source_type' => 'commission',
                'source_id' => $item->id,
                'order_id' => $item->order_id,
                'order_item_id' => $item->id,
                'level' => $level,
                'amount' => $amount,
                'currency' => $item->currency,
                'status' => 'pending', // §40 - jamais disponible immédiatement
                'description' => sprintf(
                    'Commission niveau %d (%.0f%%) sur "%s"',
                    $level,
                    self::COMMISSION_RATES[$level] * 100,
                    $item->product_name
                ),
            ]
        );

        // Ne met à jour le solde du wallet QUE si l'écriture vient d'être
        // créée (wasRecentlyCreated), pour ne jamais compter deux fois le
        // même montant si firstOrCreate retombe sur une ligne existante.
        if ($entry->wasRecentlyCreated) {
            $wallet->increment('balance_pending', $amount);
        }
    }

    /**
     * Récupère (avec verrou pessimiste) le wallet de l'affilié, ou le crée
     * s'il n'existe pas encore (§43 - chaque affilié dispose d'un
     * portefeuille interne).
     */
    private function lockOrCreateWallet(User $affiliate): Wallet
    {
        $wallet = Wallet::query()
            ->where('user_id', $affiliate->id)
            ->lockForUpdate()
            ->first();

        if ($wallet) {
            return $wallet;
        }

        $wallet = Wallet::create([
            'user_id' => $affiliate->id,
            'currency' => $affiliate->currency ?? 'XOF',
        ]);

        // Re-fetch avec verrou pour rester cohérent avec le reste de la
        // transaction (create() ne pose pas de verrou en soi).
        return Wallet::query()
            ->whereKey($wallet->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }
}
