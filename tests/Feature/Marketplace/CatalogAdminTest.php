<?php

namespace Tests\Feature\Marketplace;

use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceCategoryField;
use App\Models\User;
use App\Services\Marketplace\CategoryFieldSchemaResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogAdminTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithMarketplace;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bien-être',
            'applicable_offer_types' => ['service', 'training'],
            'is_active' => '1',
            'ruleset' => ['require_approval' => '1'],
        ], $overrides);
    }

    public function test_admin_creates_a_root_and_a_child_category(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.marketplace.categories.store'), $this->payload())
            ->assertRedirect();

        $root = MarketplaceCategory::firstWhere('name', 'Bien-être');
        $this->assertNotNull($root);
        $this->assertSame('bien-etre', $root->slug);
        $this->assertSame(0, $root->depth);
        $this->assertEqualsCanonicalizing(['service', 'training'], $root->applicable_offer_types);

        $this->actingAs($admin)->post(route('admin.marketplace.categories.store'), $this->payload([
            'name' => 'Sophrologie',
            'parent_id' => $root->id,
        ]))->assertRedirect();

        $child = MarketplaceCategory::firstWhere('name', 'Sophrologie');
        $this->assertSame(1, $child->depth);
        $this->assertSame("/{$root->id}/{$child->id}", $child->path);
    }

    public function test_commission_percent_is_stored_as_a_fraction(): void
    {
        $this->enableMarketplace();
        $this->actingAs($this->admin())->post(route('admin.marketplace.categories.store'), $this->payload([
            'commission_rate_percent' => '12.5',
        ]))->assertRedirect();

        $this->assertSame('0.1250', (string) MarketplaceCategory::first()->commission_rate);
    }

    public function test_slug_must_be_unique_within_the_same_parent(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();
        MarketplaceCategory::factory()->create(['name' => 'Livres', 'slug' => 'livres', 'parent_id' => null]);

        $this->actingAs($admin)->post(route('admin.marketplace.categories.store'), $this->payload([
            'name' => 'Livres',
        ]))->assertSessionHasErrors('slug');
    }

    public function test_a_category_cannot_be_moved_under_its_own_descendant(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();
        $root = MarketplaceCategory::factory()->create();
        $child = MarketplaceCategory::factory()->childOf($root)->create();

        $this->actingAs($admin)->put(route('admin.marketplace.categories.update', $root), $this->payload([
            'name' => $root->name,
            'parent_id' => $child->id,
        ]))->assertSessionHasErrors('parent_id');

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_a_category_with_children_cannot_be_deleted(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();
        $root = MarketplaceCategory::factory()->create();
        MarketplaceCategory::factory()->childOf($root)->create();

        $this->actingAs($admin)->delete(route('admin.marketplace.categories.destroy', $root))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('marketplace_categories', ['id' => $root->id, 'deleted_at' => null]);
    }

    public function test_admin_manages_custom_fields_and_inheritance_flows_through(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();
        $root = MarketplaceCategory::factory()->create();
        $leaf = MarketplaceCategory::factory()->childOf($root)->create();

        $this->actingAs($admin)->post(route('admin.marketplace.categories.fields.store', $root), [
            'label' => 'Matière', 'type' => 'select', 'options_text' => "Coton\nLin\nLaine",
            'is_filterable' => '1',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.marketplace.categories.fields.store', $leaf), [
            'label' => 'Taille', 'type' => 'text', 'is_required' => '1',
        ])->assertRedirect();

        $field = MarketplaceCategoryField::firstWhere('label', 'Matière');
        $this->assertSame('matiere', $field->key);
        $this->assertSame([
            ['value' => 'coton', 'label' => 'Coton'],
            ['value' => 'lin', 'label' => 'Lin'],
            ['value' => 'laine', 'label' => 'Laine'],
        ], $field->options);

        $schema = app(CategoryFieldSchemaResolver::class)->schemaFor($leaf->fresh());
        $this->assertEqualsCanonicalizing(['matiere', 'taille'], $schema->pluck('key')->all());
        $this->assertContains('matiere', app(CategoryFieldSchemaResolver::class)->filterableKeys($leaf->fresh()));
    }

    public function test_duplicate_field_key_in_a_category_is_rejected(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();
        $category = MarketplaceCategory::factory()->create();
        MarketplaceCategoryField::factory()->key('matiere')->for($category, 'category')->create();

        $this->actingAs($admin)->post(route('admin.marketplace.categories.fields.store', $category), [
            'label' => 'Autre matière', 'key' => 'matiere', 'type' => 'text',
        ])->assertSessionHasErrors('key');
    }

    public function test_non_admin_cannot_touch_the_catalog(): void
    {
        $this->enableMarketplace();
        $this->actingAs(User::factory()->create())
            ->post(route('admin.marketplace.categories.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_reorder_updates_positions(): void
    {
        $this->enableMarketplace();
        $admin = $this->admin();
        $a = MarketplaceCategory::factory()->create(['position' => 0]);
        $b = MarketplaceCategory::factory()->create(['position' => 1]);

        $this->actingAs($admin)->post(route('admin.marketplace.categories.reorder'), [
            'order' => [
                ['id' => $a->id, 'position' => 5],
                ['id' => $b->id, 'position' => 2],
            ],
        ])->assertRedirect();

        $this->assertSame(5, $a->fresh()->position);
        $this->assertSame(2, $b->fresh()->position);
    }
}
