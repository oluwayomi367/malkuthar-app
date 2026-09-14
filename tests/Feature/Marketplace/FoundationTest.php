<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCategoryField;
use App\Models\Marketplace\MarketplaceCommissionRule;
use App\Models\Marketplace\VendorProfile;
use App\Models\Setting;
use App\Services\Marketplace\CategoryFieldSchemaResolver;
use App\Services\Marketplace\MarketplaceCommissionResolver;
use App\Support\MarketplaceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_the_phase_0_tables(): void
    {
        foreach ([
            'vendor_profiles',
            'vendor_documents',
            'marketplace_categories',
            'marketplace_category_fields',
            'marketplace_commission_rules',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table manquante : {$table}");
        }
    }

    public function test_category_tree_maintains_path_and_depth(): void
    {
        $root = MarketplaceCategory::factory()->create(['name' => 'Bien-être']);
        $child = MarketplaceCategory::factory()->childOf($root)->create(['name' => 'Sophrologie']);
        $grand = MarketplaceCategory::factory()->childOf($child)->create(['name' => 'Séances découverte']);

        $this->assertSame(0, $root->fresh()->depth);
        $this->assertSame(2, $grand->fresh()->depth);
        $this->assertSame("/{$root->id}/{$child->id}/{$grand->id}", $grand->fresh()->path);
        $this->assertSame([$root->id, $child->id], $grand->fresh()->ancestorIds());
        $this->assertSame([$root->id, $child->id, $grand->id], $grand->fresh()->lineageIds());

        // Sous-arbre : la racine ramène ses 3 catégories.
        $subtree = MarketplaceCategory::query()->inSubtree($root->fresh())->pluck('id');
        $this->assertEqualsCanonicalizing([$root->id, $child->id, $grand->id], $subtree->all());

        // Ancêtres du petit-enfant, racine → parent.
        $this->assertSame(['Bien-être', 'Sophrologie'], $grand->fresh()->ancestors()->pluck('name')->all());
    }

    public function test_reparenting_rebuilds_descendant_paths(): void
    {
        $a = MarketplaceCategory::factory()->create(['name' => 'A']);
        $b = MarketplaceCategory::factory()->create(['name' => 'B']);
        $child = MarketplaceCategory::factory()->childOf($a)->create(['name' => 'Enfant']);

        $child->update(['parent_id' => $b->id]);

        $this->assertSame("/{$b->id}/{$child->id}", $child->fresh()->path);
        $this->assertSame(1, $child->fresh()->depth);
    }

    public function test_field_schema_inherits_ancestor_fields_and_leaf_overrides_win(): void
    {
        $root = MarketplaceCategory::factory()->create();
        $leaf = MarketplaceCategory::factory()->childOf($root)->create();

        MarketplaceCategoryField::factory()->key('marque')->for($root, 'category')->create(['position' => 1]);
        MarketplaceCategoryField::factory()->key('taille')->for($leaf, 'category')->create(['position' => 1]);
        // Redéfinit `marque` au niveau feuille : doit l'emporter.
        MarketplaceCategoryField::factory()->key('marque')->required()->for($leaf, 'category')->create(['position' => 0]);

        $schema = app(CategoryFieldSchemaResolver::class)->schemaFor($leaf->fresh());

        $this->assertEqualsCanonicalizing(['marque', 'taille'], $schema->pluck('key')->all());
        $this->assertTrue($schema->firstWhere('key', 'marque')->is_required, 'la définition feuille doit gagner');
        $this->assertSame($leaf->id, $schema->firstWhere('key', 'marque')->category_id);
    }

    public function test_commission_resolver_precedence(): void
    {
        $resolver = app(MarketplaceCommissionResolver::class);

        $root = MarketplaceCategory::factory()->create();
        $category = MarketplaceCategory::factory()->childOf($root)->create();
        $vendor = VendorProfile::factory()->active()->create();

        // 6. défaut plateforme (Setting)
        Setting::put('marketplace.default_commission_rate', 0.20);
        $this->assertSame(0.20, $resolver->rate($vendor, $category, 'physical'));

        // 5. barème hérité de la catégorie parente
        $root->update(['commission_rate' => 0.18]);
        $this->assertSame(0.18, $resolver->rate($vendor->fresh(), $category->fresh(), 'physical'));

        // 4. barème de la catégorie elle-même
        $category->update(['commission_rate' => 0.16]);
        $this->assertSame(0.16, $resolver->rate($vendor->fresh(), $category->fresh(), 'physical'));

        // 3. règle plateforme active
        MarketplaceCommissionRule::factory()->platform(0.14)->create();
        $this->assertSame(0.14, $resolver->rate($vendor->fresh(), $category->fresh(), 'physical'));

        // 2b. règle par type d'offre (plus spécifique que plateforme)
        MarketplaceCommissionRule::factory()->forOfferType('service', 0.12)->create();
        $this->assertSame(0.12, $resolver->rate($vendor->fresh(), $category->fresh(), 'service'));
        $this->assertSame(0.14, $resolver->rate($vendor->fresh(), $category->fresh(), 'physical'));

        // 2a. règle sur la catégorie (plus spécifique que le type)
        MarketplaceCommissionRule::factory()->forCategory($category->id, 0.10)->create();
        $this->assertSame(0.10, $resolver->rate($vendor->fresh(), $category->fresh(), 'service'));

        // 2. règle sur le vendeur (la plus spécifique)
        MarketplaceCommissionRule::factory()->forVendor($vendor->id, 0.08)->create();
        $this->assertSame(0.08, $resolver->rate($vendor->fresh(), $category->fresh(), 'service'));

        // 1. override direct du profil vendeur (court-circuite tout)
        $vendor->update(['commission_rate_override' => 0.05]);
        $this->assertSame(0.05, $resolver->rate($vendor->fresh(), $category->fresh(), 'service'));
    }

    public function test_compute_fee_applies_rule_min_and_max_bounds(): void
    {
        $resolver = app(MarketplaceCommissionResolver::class);
        $category = MarketplaceCategory::factory()->create();
        $vendor = VendorProfile::factory()->active()->create();

        MarketplaceCommissionRule::factory()->platform(0.10)->create([
            'min_fee' => 500,
            'max_fee' => 2000,
        ]);

        // 0.10 * 1000 = 100 -> relevé au plancher 500
        $this->assertSame(500.0, $resolver->computeFee($vendor, $category, 'digital', 1000)['amount']);
        // 0.10 * 50000 = 5000 -> plafonné à 2000
        $this->assertSame(2000.0, $resolver->computeFee($vendor, $category, 'digital', 50000)['amount']);
        // 0.10 * 10000 = 1000 -> dans les bornes
        $this->assertSame(1000.0, $resolver->computeFee($vendor, $category, 'digital', 10000)['amount']);
    }

    public function test_expired_commission_rule_is_ignored(): void
    {
        $resolver = app(MarketplaceCommissionResolver::class);
        $category = MarketplaceCategory::factory()->create();
        $vendor = VendorProfile::factory()->active()->create();
        Setting::put('marketplace.default_commission_rate', 0.15);

        MarketplaceCommissionRule::factory()->platform(0.05)
            ->window(now()->subDays(10)->toDateTimeString(), now()->subDay()->toDateTimeString())
            ->create();

        $this->assertSame(0.15, $resolver->rate($vendor, $category, 'physical'));
    }

    public function test_marketplace_settings_prefer_the_settings_table_over_config(): void
    {
        config()->set('marketplace.enabled', false);
        $this->assertFalse(MarketplaceSettings::enabled());

        Setting::put('marketplace.enabled', '1');
        $this->assertTrue(MarketplaceSettings::enabled());

        Setting::put('marketplace.settlement_hold_days', 7);
        $this->assertSame(7, MarketplaceSettings::settlementHoldDays());
    }
}
