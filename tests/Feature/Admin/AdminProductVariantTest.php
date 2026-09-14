<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §12 — l'administrateur gère les variantes d'un produit physique (taille,
 * couleur, modèle, format), chacune avec son SKU / stock / prix / image.
 */
class AdminProductVariantTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_cannot_manage_variants(): void
    {
        $product = Product::factory()->physical()->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/products/{$product->id}/variants", ['variants' => []])
            ->assertForbidden();
    }

    public function test_variants_are_rejected_for_non_physical_products(): void
    {
        $product = Product::factory()->create(['type' => 'digital']);

        $this->actingAs($this->admin())
            ->put("/admin/products/{$product->id}/variants", ['variants' => []])
            ->assertStatus(422);
    }

    public function test_admin_creates_variants_with_sku_price_stock_and_options(): void
    {
        $product = Product::factory()->physical()->create(['price' => 10000]);

        $this->actingAs($this->admin())
            ->from("/admin/products/{$product->id}/edit")
            ->put("/admin/products/{$product->id}/variants", [
                'variants' => [
                    ['name' => 'Terre / M', 'sku' => 'TER-M', 'price' => '12000', 'stock_available' => '7', 'is_active' => '1',
                        'options' => ['couleur' => 'Terre', 'taille' => 'M', 'modele' => '', 'format' => '', 'autre' => '']],
                    ['name' => 'Sable / L', 'sku' => 'SAB-L', 'price' => '', 'stock_available' => '3', 'is_active' => '1',
                        'options' => ['couleur' => 'Sable', 'taille' => 'L']],
                ],
            ])
            ->assertRedirect("/admin/products/{$product->id}/edit")
            ->assertSessionHas('success');

        $this->assertSame(2, $product->variants()->count());

        $terre = $product->variants()->where('sku', 'TER-M')->firstOrFail();
        $this->assertSame('12000.00', $terre->price);
        $this->assertSame(7, $terre->stock_available);
        $this->assertSame(['couleur' => 'Terre', 'taille' => 'M'], $terre->options);

        $sable = $product->variants()->where('sku', 'SAB-L')->firstOrFail();
        $this->assertNull($sable->price);                 // pas d'override -> prix produit
        $this->assertSame(10000.0, $sable->effectivePrice());
    }

    public function test_sync_updates_existing_and_deletes_absent_variants(): void
    {
        $product = Product::factory()->physical()->create();
        $keep = $product->variants()->create(['name' => 'A', 'options' => ['taille' => 'S'], 'sku' => 'A1', 'stock_available' => 5]);
        $drop = $product->variants()->create(['name' => 'B', 'options' => ['taille' => 'M'], 'sku' => 'B1', 'stock_available' => 5]);

        $this->actingAs($this->admin())
            ->put("/admin/products/{$product->id}/variants", [
                'variants' => [
                    ['id' => $keep->id, 'name' => 'A renommée', 'sku' => 'A1', 'is_active' => '1', 'options' => ['taille' => 'S']],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertSame('A renommée', $keep->fresh()->name);
        $this->assertSoftDeleted('product_variants', ['id' => $drop->id]);
        $this->assertSame(5, $keep->fresh()->stock_available); // stock inchangé par le sync
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $product = Product::factory()->physical()->create();
        $product->variants()->create(['name' => 'A', 'options' => [], 'sku' => 'DUP', 'stock_available' => 1]);

        $this->actingAs($this->admin())
            ->from("/admin/products/{$product->id}/edit")
            ->put("/admin/products/{$product->id}/variants", [
                'variants' => [['name' => 'B', 'sku' => 'DUP', 'is_active' => '1', 'options' => []]],
            ])
            ->assertSessionHasErrors('variants.0.sku');
    }

    public function test_admin_adjusts_a_variant_stock_and_a_movement_is_logged(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->physical()->create();
        $variant = $product->variants()->create(['name' => 'A', 'options' => [], 'sku' => 'A1', 'stock_available' => 4]);

        $this->actingAs($admin)
            ->from("/admin/products/{$product->id}/edit")
            ->post("/admin/products/variants/{$variant->id}/stock", ['stock_available' => 20, 'reason' => 'Réappro'])
            ->assertRedirect("/admin/products/{$product->id}/edit")
            ->assertSessionHas('success');

        $this->assertSame(20, $variant->fresh()->stock_available);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'type' => 'restocked',
            'quantity' => 16,
            'admin_id' => $admin->id,
        ]);
    }

    public function test_edit_screen_shows_the_variants_section(): void
    {
        $product = Product::factory()->physical()->create();
        $product->variants()->create(['name' => 'Ivoire / M', 'options' => ['couleur' => 'Ivoire'], 'sku' => 'IVM', 'stock_available' => 6]);

        $this->actingAs($this->admin())->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('Variantes')
            ->assertSee('Ivoire / M')
            ->assertSee('Stock par variante');
    }
}
