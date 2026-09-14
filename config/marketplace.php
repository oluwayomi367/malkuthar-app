<?php

/*
|--------------------------------------------------------------------------
| Configuration du module Marketplace multi-vendeurs
|--------------------------------------------------------------------------
|
| Ces valeurs sont les défauts. Les clés réglables en exploitation sont
| surchargées via la table `settings` (App\Models\Setting) — voir
| App\Support\MarketplaceSettings. Tout le module reste masqué tant que
| `marketplace.enabled` (Setting) n'est pas à true.
|
*/

return [

    // Drapeau maître. Tant qu'il est faux : routes publiques, entrées de nav
    // et espace vendeur renvoient 404 / sont masqués.
    'enabled' => (bool) env('MARKETPLACE_ENABLED', false),

    'commission' => [
        // Taux plateforme par défaut (fraction) si aucune règle ne s'applique.
        'default_rate' => (float) env('MARKETPLACE_DEFAULT_COMMISSION', 0.15),

        // Paliers affichés sur la page publique « Tarifs vendeur ». Le palier
        // Growth n'est PAS appliqué automatiquement : un administrateur
        // l'active manuellement (Admin\Marketplace\VendorController::commission,
        // VendorOnboardingService::setCommissionOverride) une fois le seuil de
        // chiffre d'affaires cumulé atteint — cohérent avec le reste du module
        // où toute évolution du compte vendeur passe par une validation admin.
        // Le palier Scale n'a pas de taux fixe : toujours négocié au support.
        'tiers' => [
            'starter' => [
                'label' => 'Starter',
                'rate' => (float) env('MARKETPLACE_DEFAULT_COMMISSION', 0.15),
                'ceiling_amount' => (float) env('MARKETPLACE_GROWTH_THRESHOLD', 3_000_000),
                'pitch' => 'Pour démarrer votre boutique, sans engagement.',
            ],
            'growth' => [
                'label' => 'Growth',
                'rate' => (float) env('MARKETPLACE_GROWTH_RATE', 0.10),
                'floor_amount' => (float) env('MARKETPLACE_GROWTH_THRESHOLD', 3_000_000),
                'pitch' => 'Pour les boutiques déjà établies.',
            ],
            'scale' => [
                'label' => 'Scale',
                'rate' => null,
                'pitch' => 'Volume important ? Un taux négocié avec le support.',
            ],
        ],
    ],

    'settlement' => [
        // Jours entre « payé » et « disponible » (fenêtre de rétractation).
        'hold_days' => (int) env('MARKETPLACE_SETTLEMENT_HOLD_DAYS', 14),

        // Seuil minimum d'une demande de versement (devise du vendeur).
        'min_payout_amount' => (float) env('MARKETPLACE_MIN_PAYOUT', 5000),

        // on_demand | weekly | biweekly
        'payout_schedule' => env('MARKETPLACE_PAYOUT_SCHEDULE', 'on_demand'),

        // Heures de sécurité après changement d'un moyen de versement.
        'payout_account_cooldown_hours' => (int) env('MARKETPLACE_PAYOUT_COOLDOWN_HOURS', 24),
    ],

    'offers' => [
        // Toute mise en ligne repasse en revue quand false.
        'vendor_self_publish' => (bool) env('MARKETPLACE_VENDOR_SELF_PUBLISH', false),

        // Court-circuite la revue pour les vendeurs marqués « de confiance ».
        'auto_approve_trusted_vendors' => (bool) env('MARKETPLACE_AUTO_APPROVE_TRUSTED', false),

        'max_gallery_images' => (int) env('MARKETPLACE_MAX_GALLERY', 8),
    ],

    // Préfixe des références de transaction marketplace (routage du webhook PSP).
    'payment_reference_prefix' => 'MP-',

];
