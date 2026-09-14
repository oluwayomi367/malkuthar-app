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
 * MARKETPLACE — jeu de données de démonstration + activation du module.
 *
 * Idempotent : peut être relancé (`php artisan db:seed --class=MarketplaceSeeder`).
 * Mot de passe du compte vendeur de démo : password
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

        // ---- Arbre de catégories ----
        $artisanat = $this->category('Artisanat & création', ['physical'], [
            ['key' => 'matiere', 'label' => 'Matière', 'type' => 'select', 'is_filterable' => true,
                'options' => [['value' => 'bois', 'label' => 'Bois'], ['value' => 'tissu', 'label' => 'Tissu'],
                    ['value' => 'metal', 'label' => 'Métal'], ['value' => 'ceramique', 'label' => 'Céramique']]],
            ['key' => 'fait_main', 'label' => 'Fait main', 'type' => 'boolean', 'is_filterable' => true],
        ]);
        $this->category('Coffrets', ['physical'], [], $artisanat);

        $bienEtre = $this->category('Bien-être', ['service'], []);
        $sophro = $this->category('Sophrologie', ['service'], [
            ['key' => 'format_seance', 'label' => 'Format', 'type' => 'select', 'is_filterable' => true,
                'options' => [['value' => 'individuel', 'label' => 'Individuel'], ['value' => 'collectif', 'label' => 'Collectif']]],
        ], $bienEtre);

        $this->category('Ressources digitales', ['digital'], [
            ['key' => 'format_fichier', 'label' => 'Format', 'type' => 'select', 'is_filterable' => true,
                'options' => [['value' => 'pdf', 'label' => 'PDF'], ['value' => 'audio', 'label' => 'Audio'], ['value' => 'video', 'label' => 'Vidéo']]],
        ]);

        $formations = $this->category('Formations en ligne', ['training'], [
            ['key' => 'niveau', 'label' => 'Niveau', 'type' => 'select', 'is_filterable' => true,
                'options' => [['value' => 'debutant', 'label' => 'Débutant'], ['value' => 'intermediaire', 'label' => 'Intermédiaire'], ['value' => 'avance', 'label' => 'Avancé']]],
        ]);

        // ---- Vendeur de démonstration ----
        $user = User::firstOrCreate(
            ['email' => 'vendeur@malkuthar.test'],
            [
                'first_name' => 'Awa', 'last_name' => 'Créations', 'password' => bcrypt('password'),
                'country' => 'BJ', 'currency' => 'XOF', 'role' => 'client', 'status' => 'active',
                'email_verified_at' => now(),
            ],
        );

        $vendor = VendorProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => 'Atelier Awa', 'slug' => 'atelier-awa',
                'legal_name' => 'Atelier Awa SARL',
                'bio' => 'Coffrets et objets en bois de teck fabriqués à la main à Porto-Novo. '
                    .'Chaque pièce est unique et façonnée dans notre atelier familial.',
                'region' => 'Ouémé', 'country' => 'BJ', 'city' => 'Porto-Novo',
                'contact_email' => 'vendeur@malkuthar.test',
                'status' => 'active', 'payout_currency' => 'XOF', 'approved_at' => now(),
            ],
        );

        if ($vendor->status !== 'active') {
            $vendor->update(['status' => 'active', 'approved_at' => now()]);
        }

        // ---- Offres de démo (brouillon -> soumise -> approuvée & en ligne) ----
        $this->offer($vendor, $artisanat, 'physical', [
            'title' => 'Coffret cadeau en bois de teck', 'price' => 18000,
            'summary' => 'Coffret gravé, fermeture aimantée, fait main.',
            'description' => 'Un coffret élégant en bois de teck massif, poncé et huilé à la main. '
                .'Idéal pour offrir bijoux, montres ou petits objets précieux. Dimensions 20 × 14 × 8 cm.',
            'custom_fields' => ['matiere' => 'bois', 'fait_main' => true],
            'detail' => ['stock' => 0, 'weight_grams' => 600, 'length_mm' => 200, 'width_mm' => 140, 'height_mm' => 80],
            'variants' => [
                ['option_values' => ['Finition' => 'Naturelle', 'Taille' => 'M'], 'stock' => 8, 'sku' => 'COF-NAT-M'],
                ['option_values' => ['Finition' => 'Naturelle', 'Taille' => 'L'], 'stock' => 4, 'sku' => 'COF-NAT-L', 'price_override' => 22000],
                ['option_values' => ['Finition' => 'Ébène', 'Taille' => 'M'], 'stock' => 5, 'sku' => 'COF-EBE-M', 'price_override' => 21000],
            ],
        ]);

        $this->offer($vendor, $sophro, 'service', [
            'title' => 'Séance de sophrologie découverte (45 min)', 'price' => 9000,
            'summary' => 'Première séance individuelle, en visio ou à l\'atelier.',
            'description' => 'Un temps pour souffler : respiration, détente musculaire et visualisation '
                .'positive, guidés pas à pas. Aucune expérience requise.',
            'custom_fields' => ['format_seance' => 'individuel'],
            'detail' => ['duration_minutes' => 45, 'delivery_mode' => 'either', 'capacity_per_slot' => 1],
        ]);

        $this->offer($vendor, $formations, 'training', [
            'title' => 'Formation — L\'artisanat du bois pour débuter', 'price' => 22000,
            'summary' => '5 modules vidéo, de la matière première à la finition.',
            'description' => 'Apprenez à choisir votre bois, maîtriser les outils de base et réaliser '
                .'vos premières pièces. Accès illimité, rythme libre, support PDF téléchargeable.',
            'custom_fields' => ['niveau' => 'debutant'],
            'detail' => ['format' => 'self_paced', 'language' => 'fr', 'level' => 'beginner', 'access_days' => 365],
        ]);

        Setting::put('marketplace.enabled', '1');

        $this->command?->info('Marketplace activé. Vendeur de démo : vendeur@malkuthar.test / password — boutique /place-de-marche');
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function category(string $name, array $types, array $fields, ?MarketplaceCategory $parent = null): MarketplaceCategory
    {
        $category = MarketplaceCategory::firstOrCreate(
            ['parent_id' => $parent?->id, 'slug' => Str::slug($name)],
            [
                'name' => $name,
                'applicable_offer_types' => $types,
                'is_active' => true,
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

    /**
     * @param  array<string, mixed>  $data
     */
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
