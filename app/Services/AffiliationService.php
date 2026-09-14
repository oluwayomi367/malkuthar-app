<?php

namespace App\Services;

use App\Exceptions\CommissionDistributionException;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\CommissionAvailableNotification;
use App\Notifications\NewCommissionNotification;
use Illuminate\Support\Carbon;
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
     * Taux de commission par défaut, appliqués indépendamment sur le
     * montant éligible (§29). Total maximum distribué : 30%. Surchargés
     * par config('malkuthar.affiliation.commission_rates') (§63).
     */
    private const MAX_LEVELS = 3;

    public function __construct(
        private readonly \App\Services\CommissionRateResolver $rateResolver = new \App\Services\CommissionRateResolver,
    ) {}

    /**
     * §27 — taux par niveau, résolus via CommissionRateResolver (campagne
     * active > campagne par défaut > config > standards 15/10/5).
     *
     * @return array<int, float>
     */
    private function rates(): array
    {
        return $this->rateResolver->rates();
    }

    /**
     * §42 — nombre de jours avant qu'une commission PENDING devienne
     * AVAILABLE. Réglable depuis l'administration (table `settings`), avec
     * repli sur la config puis 14 jours.
     */
    private function validationDelayDays(): int
    {
        return max(0, (int) \App\Models\Setting::get(
            'affiliation.validation_delay_days',
            config('malkuthar.affiliation.validation_delay_days', 14),
        ));
    }

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
        /** @var array<int, LedgerEntry> $createdEntries */
        $createdEntries = DB::transaction(function () use ($order) {
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

                return [];
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

                return [];
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

                return [];
            }

            $entries = [];
            foreach ($eligibleItems as $item) {
                $entries = [...$entries, ...$this->distributeCommissionsForItem($item, $affiliatesByLevel)];
            }

            Log::info('AffiliationService: commissions distribuées avec succès.', [
                'order_id' => $order->id,
                'levels_credited' => array_keys($affiliatesByLevel),
                'items_processed' => $eligibleItems->count(),
            ]);

            return $entries;
        });

        // §59 - notification hors transaction : ne dépend jamais de
        // l'intégrité financière, envoyée seulement après le commit.
        foreach ($createdEntries as $entry) {
            $entry->user?->notify(new NewCommissionNotification($entry));
        }
    }

    /**
     * §40-§42 — Fait passer à AVAILABLE toutes les commissions PENDING
     * dont la date `available_at` est atteinte, et répercute le montant
     * du solde en attente vers le solde disponible + total gagné du
     * wallet. Chaque écriture est traitée dans sa propre transaction
     * (verrous courts) et l'opération est idempotente.
     *
     * @return int nombre de commissions rendues disponibles
     */
    public function releaseDueCommissions(int $batchSize = 1000): int
    {
        $ids = LedgerEntry::query()
            ->where('source_type', 'commission')
            ->where('status', 'pending')
            ->where('available_at', '<=', now())
            // §63 — les commissions d'un affilié suspendu restent gelées
            // (« en attente ») tant que le compte n'est pas réactivé.
            ->whereHas('user', fn ($q) => $q->where('status', '!=', 'suspended'))
            ->orderBy('available_at')
            ->limit(max(1, $batchSize))
            ->pluck('id');

        $released = 0;

        foreach ($ids as $id) {
            $entry = $this->releaseCommissionEntry((string) $id);

            if ($entry) {
                $released++;
                // §59 - notification hors transaction.
                $entry->user?->notify(new CommissionAvailableNotification($entry));
            }
        }

        if ($released > 0) {
            Log::info('AffiliationService: commissions rendues disponibles.', ['count' => $released]);
        }

        return $released;
    }

    private function releaseCommissionEntry(string $entryId): ?LedgerEntry
    {
        return DB::transaction(function () use ($entryId) {
            /** @var LedgerEntry|null $entry */
            $entry = LedgerEntry::query()->whereKey($entryId)->lockForUpdate()->first();

            if (! $entry || $entry->status !== 'pending' || $entry->source_type !== 'commission') {
                return null;
            }

            if ($entry->available_at instanceof Carbon && $entry->available_at->isFuture()) {
                return null;
            }

            $wallet = Wallet::query()->whereKey($entry->wallet_id)->lockForUpdate()->first();

            // §41 — transition consignée.
            $entry->transitionTo('available', 'commission_release');

            if ($wallet) {
                // §43 — le montant validé quitte "en attente" pour
                // "disponible" et alimente le total historique gagné.
                $wallet->decrement('balance_pending', $entry->amount);
                $wallet->increment('balance_available', $entry->amount);
                $wallet->increment('total_earned', $entry->amount);
            }

            return $entry;
        });
    }

    /**
     * §80 — Remboursement d'une commande : les commissions liées sont
     * annulées (si encore en attente) ou reprises (si déjà disponibles /
     * payées) via une écriture de sens inverse. On ne modifie jamais une
     * écriture de commission "en place" : une commission disponible/payée
     * reçoit une écriture `commission_reversal` distincte (§49).
     *
     * Idempotent : si la reprise a déjà eu lieu, l'appel est sans effet.
     *
     * @return int nombre de commissions annulées ou reprises
     */
    public function reverseCommissionsForOrder(Order $order): int
    {
        return DB::transaction(function () use ($order) {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $commissions = LedgerEntry::query()
                ->where('order_id', $order->id)
                ->where('source_type', 'commission')
                ->whereIn('status', ['pending', 'available', 'processing', 'paid'])
                ->lockForUpdate()
                ->get();

            $handled = 0;

            foreach ($commissions as $commission) {
                $wallet = Wallet::query()->whereKey($commission->wallet_id)->lockForUpdate()->first();

                if ($commission->status === 'pending') {
                    // Jamais devenue disponible : simple annulation.
                    $commission->transitionTo('cancelled', 'refund_cancellation', 'Remboursement de la commande '.$order->order_number);
                    $wallet?->decrement('balance_pending', $commission->amount);
                } else {
                    // Déjà disponible ou payée : écriture de reprise
                    // distincte (le solde disponible peut devenir négatif,
                    // ce qui reste traçable — §67).
                    LedgerEntry::create([
                        'wallet_id' => $commission->wallet_id,
                        'user_id' => $commission->user_id,
                        'reference' => 'COM-REV-'.$commission->id,
                        'direction' => 'debit',
                        'source_type' => 'commission_reversal',
                        'source_id' => $commission->id,
                        'order_id' => $commission->order_id,
                        'order_item_id' => $commission->order_item_id,
                        'level' => $commission->level,
                        'amount' => $commission->amount,
                        'currency' => $commission->currency,
                        'status' => 'reversed',
                        'description' => 'Reprise de commission suite au remboursement de la commande '.$order->order_number,
                    ]);

                    $commission->transitionTo('reversed', 'refund_reversal', 'Remboursement de la commande '.$order->order_number);
                    $wallet?->decrement('balance_available', $commission->amount);
                    $wallet?->decrement('total_earned', $commission->amount);
                }

                $handled++;
            }

            if ($handled > 0) {
                Log::warning('AffiliationService: commissions reprises suite à remboursement.', [
                    'order_id' => $order->id,
                    'count' => $handled,
                ]);
            }

            return $handled;
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
     * @return array<int, LedgerEntry> écritures nouvellement créées
     */
    private function distributeCommissionsForItem(OrderItem $item, array $affiliatesByLevel): array
    {
        // Montant éligible = base de calcul figée au moment de l'achat (§5).
        $eligibleAmount = (float) ($item->commission_base_amount
            ?? ($item->total_price - $item->discount_amount));

        if ($eligibleAmount <= 0) {
            return [];
        }

        $entries = [];

        // §27 - taux résolus UNE fois pour la commande (campagne éventuelle).
        $resolution = $this->rateResolver->resolve();
        $campaignId = $resolution['campaign']?->id;

        foreach ($resolution['rates'] as $level => $rate) {
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

            $entry = $this->creditPendingCommission(
                affiliate: $affiliate,
                item: $item,
                level: $level,
                amount: $amount,
                rate: (float) $rate,
                campaignId: $campaignId,
            );

            if ($entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Crée la LedgerEntry PENDING pour un affilié/niveau/ligne donnés et
     * met à jour le solde en attente (balance_pending) de son wallet.
     *
     * @return LedgerEntry|null l'écriture, uniquement si nouvellement créée.
     */
    private function creditPendingCommission(
        User $affiliate,
        OrderItem $item,
        int $level,
        float $amount,
        ?float $rate = null,
        ?string $campaignId = null,
    ): ?LedgerEntry {
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
                // §27 - taux réellement appliqué, figé sur l'écriture : une
                // campagne future ne réécrit jamais l'historique.
                'commission_rate' => $rate,
                'commission_campaign_id' => $campaignId,
                'amount' => $amount,
                'currency' => $item->currency,
                'status' => 'pending', // §40 - jamais disponible immédiatement
                // §42 - date à laquelle la commission deviendra retirable.
                'available_at' => now()->addDays($this->validationDelayDays()),
                'description' => sprintf(
                    'Commission niveau %d (%.0f%%) sur "%s"',
                    $level,
                    ($rate ?? $this->rates()[$level]) * 100,
                    $item->product_name
                ),
            ]
        );

        // Ne met à jour le solde du wallet QUE si l'écriture vient d'être
        // créée (wasRecentlyCreated), pour ne jamais compter deux fois le
        // même montant si firstOrCreate retombe sur une ligne existante.
        if ($entry->wasRecentlyCreated) {
            $wallet->increment('balance_pending', $amount);

            return $entry;
        }

        return null;
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
