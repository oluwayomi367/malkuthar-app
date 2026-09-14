<?php

/*
|--------------------------------------------------------------------------
| Configuration métier MALKUTHAR
|--------------------------------------------------------------------------
|
| Paramètres du programme d'affiliation et des retraits, pilotables depuis
| l'administration à terme (§42, §51, §57, §63). Les valeurs ci-dessous
| sont les valeurs par défaut du cahier des charges.
|
*/

return [

    'affiliation' => [
        // §28-§29 — 3 niveaux rémunérés, taux indépendants sur le montant
        // éligible. Total maximum distribué : 30 %.
        'commission_rates' => [
            1 => (float) env('MALKUTHAR_COMMISSION_L1', 0.15),
            2 => (float) env('MALKUTHAR_COMMISSION_L2', 0.10),
            3 => (float) env('MALKUTHAR_COMMISSION_L3', 0.05),
        ],

        // §37 — profondeur d'arbre conservée au-delà, mais seuls ces
        // niveaux génèrent une commission.
        'paid_levels' => 3,

        // §42 — nombre de jours avant qu'une commission PENDING devienne
        // AVAILABLE (retirable). Configurable depuis l'admin.
        'validation_delay_days' => (int) env('MALKUTHAR_VALIDATION_DELAY_DAYS', 14),

        // §33 — préfixe du lien de parrainage : {app.url}/ref/{code}
        'referral_path' => 'ref',

        // §32 — format de l'identifiant affilié unique et permanent.
        'affiliate_id_prefix' => 'MAL-AFF-',
        'affiliate_id_padding' => 6,

        // §35 — durée de vie du cookie d'attribution du parrain (jours).
        'referral_cookie_days' => (int) env('MALKUTHAR_REFERRAL_COOKIE_DAYS', 30),
    ],

    'digital_delivery' => [
        // §9 — limitation des téléchargements par ligne de commande
        // (0 = illimité). Le lien lui-même reste temporaire (7 jours,
        // DigitalDeliveryService::LINK_VALIDITY_DAYS).
        'max_downloads' => (int) env('MALKUTHAR_MAX_DOWNLOADS', 5),
    ],

    'withdrawals' => [
        // §51 — seuil minimum de retrait (configurable).
        'minimum_amount' => (float) env('MALKUTHAR_WITHDRAWAL_MIN', 1000),

        // §51 — plafond éventuel par demande (null = pas de plafond).
        'maximum_amount' => env('MALKUTHAR_WITHDRAWAL_MAX') !== null
            ? (float) env('MALKUTHAR_WITHDRAWAL_MAX')
            : null,

        // §57 — frais de retrait. type: 'none' | 'fixed' | 'percent'
        'fee' => [
            'type' => env('MALKUTHAR_WITHDRAWAL_FEE_TYPE', 'none'),
            'fixed' => (float) env('MALKUTHAR_WITHDRAWAL_FEE_FIXED', 0),
            'percent' => (float) env('MALKUTHAR_WITHDRAWAL_FEE_PERCENT', 0),
        ],

        // §56 — délai de sécurité (heures) après modification d'un moyen de
        // retrait avant qu'un nouveau retrait soit autorisé.
        'cooldown_after_account_change_hours' => (int) env('MALKUTHAR_WITHDRAWAL_COOLDOWN_HOURS', 24),
    ],

    // §75 — internationalisation. L'architecture doit permettre d'ajouter
    // des langues et des devises sans réécriture.
    'i18n' => [
        'supported_locales' => explode(',', (string) env('MALKUTHAR_LOCALES', 'fr,en')),
        'default_locale' => env('MALKUTHAR_DEFAULT_LOCALE', 'fr'),
        'supported_currencies' => explode(',', (string) env('MALKUTHAR_CURRENCIES', 'XOF,XAF,EUR,USD')),
        'default_currency' => env('MALKUTHAR_DEFAULT_CURRENCY', 'XOF'),
    ],

    // §72 — pixels et outils de mesure. Chargés uniquement si l'ID est
    // renseigné (sinon aucun script injecté).
    'tracking' => [
        'meta_pixel_id' => env('META_PIXEL_ID'),
        'tiktok_pixel_id' => env('TIKTOK_PIXEL_ID'),
        'ga_measurement_id' => env('GA_MEASUREMENT_ID'),
        'gtm_container_id' => env('GTM_CONTAINER_ID'),
    ],

    // §74 — SEO
    'seo' => [
        'site_name' => env('MALKUTHAR_SITE_NAME', 'MALKUTHAR'),
        'default_title' => env('MALKUTHAR_SEO_TITLE', 'MALKUTHAR — Produits digitaux, physiques et services'),
        'default_description' => env('MALKUTHAR_SEO_DESCRIPTION', 'Boutique MALKUTHAR : produits digitaux, produits physiques et services, avec programme d\'affiliation.'),
    ],

    // Contact / support
    'contact' => [
        'email' => env('MALKUTHAR_CONTACT_EMAIL', 'contact@malkuthar.com'),
        'phone' => env('MALKUTHAR_CONTACT_PHONE'),
        'whatsapp' => env('MALKUTHAR_CONTACT_WHATSAPP'),
        'hours' => env('MALKUTHAR_CONTACT_HOURS', 'Du lundi au vendredi, 9 h – 18 h (GMT).'),
        'company' => env('MALKUTHAR_COMPANY', 'MALKUTHAR SARL'),
        'address' => env('MALKUTHAR_ADDRESS', 'Cotonou, Bénin'),
    ],

];
