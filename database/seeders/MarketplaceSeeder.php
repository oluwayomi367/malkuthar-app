<?php

namespace Database\Seeders;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCommissionRule;
use App\Models\Marketplace\MarketplaceOffer;
use App\Models\Marketplace\VendorProfile;
use App\Models\Setting;
use App\Models\User;
use App\Services\Marketplace\OfferModerationService;
use App\Services\Marketplace\OfferSubmissionService;
use App\Services\Marketplace\OfferWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * MARKETPLACE — jeu de données de démonstration complet pour les 4 Pôles étanches :
 * 1. Mode, Beauté & Cosmétiques
 * 2. Résidences, Guest Houses & Immobilier
 * 3. Services & Praticiens Spécialisés
 * 4. Savoirs & Formations en Ligne
 */
class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Barème plateforme par défaut : 15 % ----
        MarketplaceCommissionRule::firstOrCreate(
            ['scope' => 'platform', 'category_id' => null, 'vendor_profile_id' => null, 'offer_type' => null],
            ['rate' => 0.15, 'priority' => 0, 'is_active' => true],
        );

        // =========================================================================
        // PÔLE 1 : MODE, BEAUTÉ & COSMÉTIQUES
        // =========================================================================
        $poleMode = $this->category(
            'Mode, Beauté & Cosmétiques',
            ['physical'],
            [
                ['key' => 'genre', 'label' => 'Genre', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'femme', 'label' => 'Femme'], ['value' => 'homme', 'label' => 'Homme'], ['value' => 'mixte', 'label' => 'Mixte']]],
                ['key' => 'origine', 'label' => 'Origine', 'type' => 'text', 'is_filterable' => true],
            ],
            null,
            0
        );

        $cosmetiques = $this->category(
            'Cosmétiques & Rituels Naturels',
            ['physical'],
            [
                ['key' => 'type_soin', 'label' => 'Type de soin', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'visage', 'label' => 'Soin visage'], ['value' => 'corps', 'label' => 'Soin corps'], ['value' => 'cheveux', 'label' => 'Soin capillaire']]],
                ['key' => 'bio_naturel', 'label' => '100% Naturel & Bio', 'type' => 'boolean', 'is_filterable' => true],
            ],
            $poleMode,
            1
        );

        $modeVêtements = $this->category(
            'Mode & Prêt-à-porter',
            ['physical'],
            [
                ['key' => 'matiere', 'label' => 'Matière textile', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'lin', 'label' => 'Lin naturel'], ['value' => 'soie', 'label' => 'Soie'], ['value' => 'coton_tisse', 'label' => 'Coton tissé'], ['value' => 'bazin', 'label' => 'Bazin riche']]],
            ],
            $poleMode,
            2
        );

        $artisanat = $this->category(
            'Artisanat & Maroquinerie',
            ['physical'],
            [
                ['key' => 'matiere', 'label' => 'Matière', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'cuir', 'label' => 'Cuir véritable'], ['value' => 'bois', 'label' => 'Bois précieux'], ['value' => 'bronze', 'label' => 'Bronze / Métal']]],
                ['key' => 'fait_main', 'label' => 'Fait main', 'type' => 'boolean', 'is_filterable' => true],
            ],
            $poleMode,
            3
        );

        // =========================================================================
        // PÔLE 2 : RÉSIDENCES, GUEST HOUSES & IMMOBILIER
        // =========================================================================
        $poleImmo = $this->category(
            'Résidences, Guest Houses & Immobilier',
            ['service', 'physical'],
            [
                ['key' => 'ville', 'label' => 'Ville', 'type' => 'select', 'is_filterable' => true,
                    'options' => [
                        ['value' => 'cotonou', 'label' => 'Cotonou'],
                        ['value' => 'abidjan', 'label' => 'Abidjan'],
                        ['value' => 'lome', 'label' => 'Lomé'],
                        ['value' => 'dakar', 'label' => 'Dakar'],
                    ]],
                ['key' => 'quartier', 'label' => 'Quartier', 'type' => 'text', 'is_filterable' => true],
                ['key' => 'wifi', 'label' => 'Wi-Fi haut débit', 'type' => 'boolean', 'is_filterable' => true],
                ['key' => 'groupe_secours', 'label' => 'Groupe électrogène', 'type' => 'boolean', 'is_filterable' => true],
                ['key' => 'piscine', 'label' => 'Piscine', 'type' => 'boolean', 'is_filterable' => true],
            ],
            null,
            1
        );

        $guestHouses = $this->category(
            'Guest Houses & Chambres d\'Hôtes',
            ['service'],
            [
                ['key' => 'capacite_personnes', 'label' => 'Capacité (personnes)', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => '1', 'label' => '1 personne'], ['value' => '2', 'label' => '2 personnes'], ['value' => '4', 'label' => 'Famille (4 pers.)']]],
                ['key' => 'petit_dejeuner', 'label' => 'Petit-déjeuner inclus', 'type' => 'boolean', 'is_filterable' => true],
            ],
            $poleImmo,
            1
        );

        $residencesMeublees = $this->category(
            'Résidences Meublées Standing',
            ['service'],
            [
                ['key' => 'nombre_chambres', 'label' => 'Nombre de chambres', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => '1', 'label' => '1 chambre (Suite)'], ['value' => '2', 'label' => '2 chambres'], ['value' => '3', 'label' => '3 chambres et +']]],
                ['key' => 'securite_24', 'label' => 'Sécurité 24h/24', 'type' => 'boolean', 'is_filterable' => true],
            ],
            $poleImmo,
            2
        );

        $agencesImmo = $this->category(
            'Agences Immobilières & Domaines',
            ['service', 'physical'],
            [
                ['key' => 'type_transaction', 'label' => 'Type d\'offre', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'location_mois', 'label' => 'Location mensuelle'], ['value' => 'vente', 'label' => 'Vente de bien titré']]],
            ],
            $poleImmo,
            3
        );

        // =========================================================================
        // PÔLE 3 : SERVICES & PRATICIENS SPÉCIALISÉS
        // =========================================================================
        $poleServices = $this->category(
            'Services & Praticiens Spécialisés',
            ['service'],
            [
                ['key' => 'mode_consultation', 'label' => 'Format de consultation', 'type' => 'select', 'is_filterable' => true,
                    'options' => [
                        ['value' => 'visio', 'label' => 'Visioconférence en direct'],
                        ['value' => 'cabinet', 'label' => 'En cabinet / Présentiel'],
                        ['value' => 'mixte', 'label' => 'Au choix (Visio ou Cabinet)'],
                    ]],
                ['key' => 'duree_minutes', 'label' => 'Durée de la séance', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => '30', 'label' => '30 min'], ['value' => '45', 'label' => '45 min'], ['value' => '60', 'label' => '1 heure'], ['value' => '90', 'label' => '1h30']]],
            ],
            null,
            2
        );

        $consulting = $this->category(
            'Consultations & Coaching Professionnel',
            ['service'],
            [
                ['key' => 'domaine', 'label' => 'Spécialité', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'strategie', 'label' => 'Stratégie & Affaires'], ['value' => 'juridique', 'label' => 'Conseil juridique'], ['value' => 'mentorat', 'label' => 'Mentorat d\'excellence']]],
            ],
            $poleServices,
            1
        );

        $bienEtre = $this->category(
            'Soins, Thérapies & Bien-être',
            ['service'],
            [
                ['key' => 'type_pratique', 'label' => 'Discipline', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'sophrologie', 'label' => 'Sophrologie'], ['value' => 'holistique', 'label' => 'Soin holistique'], ['value' => 'relaxation', 'label' => 'Relaxation profonde']]],
            ],
            $poleServices,
            2
        );

        // =========================================================================
        // PÔLE 4 : SAVOIRS & FORMATIONS EN LIGNE
        // =========================================================================
        $poleAcademy = $this->category(
            'Savoirs & Formations en Ligne',
            ['digital', 'training'],
            [
                ['key' => 'langue', 'label' => 'Langue', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'fr', 'label' => 'Français'], ['value' => 'en', 'label' => 'Anglais']]],
            ],
            null,
            3
        );

        $formations = $this->category(
            'Formations Vidéo & Masterclasses',
            ['training'],
            [
                ['key' => 'niveau', 'label' => 'Niveau d\'apprentissage', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'debutant', 'label' => 'Débutant'], ['value' => 'intermediaire', 'label' => 'Intermédiaire'], ['value' => 'avance', 'label' => 'Avancé']]],
            ],
            $poleAcademy,
            1
        );

        $ebooks = $this->category(
            'Ressources & Ebooks Digitaux',
            ['digital'],
            [
                ['key' => 'format_fichier', 'label' => 'Format', 'type' => 'select', 'is_filterable' => true,
                    'options' => [['value' => 'pdf', 'label' => 'PDF illustré'], ['value' => 'audio', 'label' => 'Audio MP3'], ['value' => 'pack', 'label' => 'Pack complet (PDF + Audio)']]],
            ],
            $poleAcademy,
            2
        );

        // =========================================================================
        // VENDEURS DE DÉMONSTRATION CERTIFIÉS PAR L'ADMINISTRATION
        // =========================================================================
        $vendeurAwa = $this->getOrCreateVendor(
            'vendeur@malkuthar.test',
            'Awa',
            'Créations',
            'Atelier Awa',
            'Atelier Awa SARL',
            'Maison de création de prêt-à-porter, cosmétiques naturels et coffrets d\'artisanat précieux.',
            'Ouémé',
            'Porto-Novo',
            'BJ',
            'fashion_beauty'
        );

        $vendeurImmo = $this->getOrCreateVendor(
            'residences@malkuthar.test',
            'Séraphin',
            'Dossou',
            'Résidences & Guest Houses Oasis',
            'Oasis Prestige Hospitality SARL',
            'Gestionnaire d\'hébergements haut standing, suites meublées et guest houses avec services hôteliers.',
            'Littoral',
            'Cotonou',
            'BJ',
            'real_estate_hospitality'
        );

        $vendeurPraticien = $this->getOrCreateVendor(
            'cabinet@malkuthar.test',
            'Dr.',
            'Koffi',
            'Cabinet d\'Accompagnement Kemet',
            'Kemet Consulting International',
            'Cabinet d\'expertise, consultations stratégiques et soins thérapeutiques pour dirigeants et particuliers.',
            'Maritime',
            'Lomé',
            'TG',
            'services_wellness'
        );

        // =========================================================================
        // OFFRES DÉMONSTRATION DES 4 PÔLES (Approuvées & en ligne)
        // =========================================================================

        // --- Pôle 1 : Mode & Cosmétiques ---
        $this->offer($vendeurAwa, $cosmetiques, 'physical', [
            'title' => 'Sérum Éclat Moringa & Beurre de Karité Sauvage',
            'price' => 24000,
            'sale_price' => 18500, // En promotion avec cocarde
            'summary' => 'Soin visage et corps 100% biologique pressé à froid.',
            'description' => 'Un élixir précieux associant l\'huile pure de graines de Moringa du Bénin au beurre de karité vierge non raffiné. Régénère en profondeur, protège des agressions climatiques et illumine le teint.',
            'main_image_path' => 'images/categories/physical.jpg',
            'custom_fields' => ['type_soin' => 'visage', 'bio_naturel' => true, 'origine' => 'Bénin'],
            'detail' => ['stock' => 15, 'weight_grams' => 250],
            'variants' => [
                ['option_values' => ['Contenance' => '50 ml', 'Couleur' => 'Or naturel'], 'stock' => 10, 'sku' => 'SER-MOR-50'],
                ['option_values' => ['Contenance' => '100 ml', 'Couleur' => 'Or naturel'], 'stock' => 5, 'sku' => 'SER-MOR-100', 'price_override' => 28000],
            ],
        ]);

        $this->offer($vendeurAwa, $modeVêtements, 'physical', [
            'title' => 'Tunique en Lin Naturel & Broderies Fait Main',
            'price' => 38000,
            'sale_price' => 29000,
            'summary' => 'Vêtement mixte en lin lavé noble avec finitions soignées.',
            'description' => 'Confectionnée par des maîtres tailleurs dans un lin dense et respirant. Coupe ample, col officier raffiné et broderies discrètes ton sur ton aux poignets.',
            'main_image_path' => 'images/catalog-banner.jpg',
            'custom_fields' => ['matiere' => 'lin', 'genre' => 'mixte'],
            'detail' => ['stock' => 12, 'weight_grams' => 450],
            'variants' => [
                ['option_values' => ['Taille' => 'M', 'Couleur' => 'Crème'], 'stock' => 5, 'sku' => 'TUN-CRE-M'],
                ['option_values' => ['Taille' => 'L', 'Couleur' => 'Crème'], 'stock' => 4, 'sku' => 'TUN-CRE-L'],
                ['option_values' => ['Taille' => 'M', 'Couleur' => 'Noir ébène'], 'stock' => 3, 'sku' => 'TUN-EBE-M', 'price_override' => 39000],
            ],
        ]);

        $this->offer($vendeurAwa, $artisanat, 'physical', [
            'title' => 'Coffret Prestige en Bois de Teck Sculpté',
            'price' => 22000,
            'summary' => 'Écrin d\'artisanat d\'art poncé et huilé à la main.',
            'description' => 'Coffret en bois massif imputrescible avec fermeture aimantée et velours intérieur. Idéal pour garder bijoux précieux, montres ou médaillons.',
            'main_image_path' => 'images/categories/physical.jpg',
            'custom_fields' => ['matiere' => 'bois', 'fait_main' => true],
            'detail' => ['stock' => 8, 'weight_grams' => 600],
        ]);

        // --- Pôle 2 : Résidences & Guest Houses ---
        $this->offer($vendeurImmo, $guestHouses, 'service', [
            'title' => 'Suite Royale avec Piscine — Guest House Oasis Cotonou',
            'price' => 55000,
            'sale_price' => 45000,
            'summary' => 'Séjour de sérénité au cœur du quartier résidentiel Haie Vive.',
            'description' => 'Chambre d\'hôtes de grand standing avec lit King Size, salle de bain en marbre, accès direct à la piscine privée, Wi-Fi très haut débit fibre et groupe électrogène permanent.',
            'main_image_path' => 'images/categories/service.jpg',
            'custom_fields' => [
                'ville' => 'cotonou',
                'quartier' => 'Haie Vive',
                'wifi' => true,
                'groupe_secours' => true,
                'piscine' => true,
                'capacite_personnes' => '2',
                'petit_dejeuner' => true,
            ],
            'detail' => [
                'duration_minutes' => 1440, // 24h = 1 nuitée
                'delivery_mode' => 'onsite',
                'location' => 'Haie Vive, Cotonou, Bénin',
                'capacity_per_slot' => 2,
            ],
        ]);

        $this->offer($vendeurImmo, $residencesMeublees, 'service', [
            'title' => 'Appartement Meublé Standing 3 Pièces — Cocody Riviera',
            'price' => 75000,
            'summary' => 'Résidence sécurisée 24/7 avec vue dégagée et parking privé.',
            'description' => 'Appartement spacieux entièrement équipé (cuisine américaine, salon cuir, smart TV, climatisation split intégrale, service de ménage quotidien). Parfait pour séjours d\'affaires ou vacances en famille.',
            'main_image_path' => 'images/catalog-banner.jpg',
            'custom_fields' => [
                'ville' => 'abidjan',
                'quartier' => 'Cocody Riviera',
                'wifi' => true,
                'groupe_secours' => true,
                'piscine' => false,
                'nombre_chambres' => '2',
                'securite_24' => true,
            ],
            'detail' => [
                'duration_minutes' => 1440,
                'delivery_mode' => 'onsite',
                'location' => 'Cocody Riviera 3, Abidjan, Côte d\'Ivoire',
                'capacity_per_slot' => 4,
            ],
        ]);

        // --- Pôle 3 : Services & Praticiens ---
        $this->offer($vendeurPraticien, $consulting, 'service', [
            'title' => 'Consultation Stratégique & Audit de Patrimoine (1h)',
            'price' => 30000,
            'sale_price' => 25000,
            'summary' => 'Séance d\'orientation confidentielle avec un expert certifié.',
            'description' => 'Analyse approfondie de votre situation patrimoniale, fiscale ou d\'affaires. Synthèse écrite transmise à l\'issue de la consultation et feuille de route opérationnelle.',
            'main_image_path' => 'images/categories/service.jpg',
            'custom_fields' => [
                'mode_consultation' => 'visio',
                'duree_minutes' => '60',
                'domaine' => 'strategie',
            ],
            'detail' => [
                'duration_minutes' => 60,
                'delivery_mode' => 'online',
                'capacity_per_slot' => 1,
            ],
        ]);

        $this->offer($vendeurPraticien, $bienEtre, 'service', [
            'title' => 'Séance de Réalignement Énergétique & Détente (45 min)',
            'price' => 15000,
            'summary' => 'Thérapie apaisante guidée pour relâcher le stress et retrouver la clarté.',
            'description' => 'Protocole combinant respiration consciente, relaxation progressive et rééquilibrage. En cabinet privé ou en visioconférence sécurisée selon votre préférence.',
            'main_image_path' => 'images/categories/service.jpg',
            'custom_fields' => [
                'mode_consultation' => 'mixte',
                'duree_minutes' => '45',
                'type_pratique' => 'holistique',
            ],
            'detail' => [
                'duration_minutes' => 45,
                'delivery_mode' => 'either',
                'capacity_per_slot' => 1,
            ],
        ]);

        // --- Pôle 4 : Savoirs & Formations ---
        $this->offer($vendeurAwa, $formations, 'training', [
            'title' => 'Formation Vidéo — Maîtriser l\'Artisanat & la Maroquinerie',
            'price' => 28000,
            'summary' => 'Parcours structuré en 6 modules pour concevoir des pièces de collection.',
            'description' => 'Apprenez pas à pas le travail des cuirs fins, le choix des matières premières africaines et les techniques d\'assemblage haute durabilité. Accès immédiat illimité à vie.',
            'main_image_path' => 'images/categories/digital.jpg',
            'custom_fields' => ['niveau' => 'intermediaire', 'langue' => 'fr'],
            'detail' => ['format' => 'self_paced', 'language' => 'fr', 'level' => 'intermediate', 'access_days' => 365],
        ]);

        $this->offer($vendeurAwa, $ebooks, 'digital', [
            'title' => 'Guide Numérique — Rituels de Beauté & Plantes Ouest-Africaines',
            'price' => 9500,
            'summary' => 'Ebook illustré de 120 pages avec fiches pratiques et recettes.',
            'description' => 'Découvrez les vertus secrètes du Moringa, de l\'huile de Baobab et du Karité pur. Conseils de formulation maison et rituels quotidiens de régénération.',
            'main_image_path' => 'images/categories/digital.jpg',
            'custom_fields' => ['format_fichier' => 'pdf', 'langue' => 'fr'],
            'detail' => ['delivery_type' => 'file', 'version_label' => 'v2.0'],
        ]);

        Setting::put('marketplace.enabled', '1');

        $this->command?->info('Marketplace multi-secteurs activée avec les 4 Pôles étanches.');
    }

    private function category(string $name, array $types, array $fields, ?MarketplaceCategory $parent = null, int $position = 0): MarketplaceCategory
    {
        $category = MarketplaceCategory::firstOrCreate(
            ['parent_id' => $parent?->id, 'slug' => Str::slug($name)],
            [
                'name' => $name,
                'applicable_offer_types' => $types,
                'is_active' => true,
                'position' => $position,
                'ruleset' => ['require_approval' => true],
            ],
        );

        foreach ($fields as $i => $f) {
            $category->fields()->firstOrCreate(
                ['key' => $f['key']],
                [
                    'label' => $f['label'],
                    'type' => $f['type'],
                    'options' => $f['options'] ?? null,
                    'is_required' => false,
                    'is_filterable' => $f['is_filterable'] ?? false,
                    'is_variant_axis' => false,
                    'position' => $i,
                ],
            );
        }

        return $category;
    }

    private function getOrCreateVendor(string $email, string $firstName, string $lastName, string $displayName, string $legalName, string $bio, string $region, string $city, string $country, string $sector): VendorProfile
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => bcrypt('password'),
                'country' => $country,
                'currency' => 'XOF',
                'role' => 'client',
                'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $vendor = VendorProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $displayName,
                'slug' => Str::slug($displayName),
                'legal_name' => $legalName,
                'bio' => $bio,
                'region' => $region,
                'country' => $country,
                'city' => $city,
                'contact_email' => $email,
                'status' => 'active',
                'payout_currency' => 'XOF',
                'approved_at' => now(),
                'metadata' => ['sector' => $sector],
            ],
        );

        if ($vendor->status !== 'active') {
            $vendor->update(['status' => 'active', 'approved_at' => now()]);
        }

        return $vendor;
    }

    private function offer(VendorProfile $vendor, MarketplaceCategory $category, string $type, array $data): void
    {
        if (MarketplaceOffer::where('vendor_profile_id', $vendor->id)->where('title', $data['title'])->exists()) {
            return;
        }

        $offer = app(OfferWriter::class)->create($vendor, array_merge($data, [
            'type' => $type,
            'category_id' => $category->id,
        ]));

        $admin = User::where('role', 'admin')->first();

        if ($admin) {
            app(OfferSubmissionService::class)->submit($offer->fresh(), $vendor->user);
            app(OfferModerationService::class)->approve($offer->fresh(), $admin);
        }
    }
}
