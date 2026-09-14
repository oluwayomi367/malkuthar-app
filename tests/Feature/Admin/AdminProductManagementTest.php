<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * §6-§18, §60 — l'administrateur crée / modifie les produits lui-même,
 * téléverse les visuels (§13) et pilote entièrement le stock des produits
 * physiques (§10-§12).
 */
class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (glob(public_path('uploads/products/*')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function digitalPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ebook Croissance',
            'slug' => '',
            'description' => 'Un guide complet.',
            'type' => 'digital',
            'is_affiliable' => '1',
            'price' => '15000',
            'currency' => 'XOF',
            'status' => 'published',
            'is_available' => '1',
            'file_path' => 'products/ebook-croissance.pdf',
        ], $overrides);
    }

    public function test_non_admin_cannot_reach_product_management(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get('/admin/products')->assertForbidden();
        $this->actingAs($client)->get('/admin/products/create')->assertForbidden();
        $this->actingAs($client)->post('/admin/products', $this->digitalPayload())->assertForbidden();
    }

    public function test_admin_sees_the_product_list(): void
    {
        $product = Product::factory()->create(['name' => 'Produit Vitrine']);

        $this->actingAs($this->admin())->get('/admin/products')
            ->assertOk()
            ->assertSee('Produit Vitrine')
            ->assertSee('Nouveau produit');
    }

    public function test_admin_can_create_a_digital_product_and_slug_is_generated(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/products', $this->digitalPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $product = Product::where('name', 'Ebook Croissance')->firstOrFail();

        $this->assertSame('digital', $product->type);
        $this->assertTrue($product->is_affiliable);
        $this->assertSame('ebook-croissance', $product->slug);
    }

    public function test_a_non_digital_product_cannot_be_marked_affiliable(): void
    {
        $payload = $this->digitalPayload([
            'type' => 'physical',
            'is_affiliable' => '1',
            'sku' => 'MUG-001',
            'file_path' => null,
            'stock_available' => '20',
        ]);

        $this->actingAs($this->admin())
            ->from('/admin/products/create')
            ->post('/admin/products', $payload)
            ->assertRedirect('/admin/products/create')
            ->assertSessionHasErrors('is_affiliable');

        $this->assertDatabaseMissing('products', ['sku' => 'MUG-001']);
    }

    public function test_admin_can_create_a_physical_product_with_initial_stock(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', $this->digitalPayload([
            'name' => 'Mug MALKUTHAR',
            'type' => 'physical',
            'is_affiliable' => '0',
            'file_path' => null,
            'sku' => 'MUG-042',
            'stock_available' => '30',
        ]))->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'sku' => 'MUG-042',
            'type' => 'physical',
            'stock_available' => 30,
            'is_affiliable' => false,
        ]);
    }

    public function test_admin_can_update_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'Ancien nom', 'price' => 5000]);

        $this->actingAs($this->admin())
            ->put("/admin/products/{$product->id}", $this->digitalPayload([
                'name' => 'Nouveau nom',
                'slug' => $product->slug,
                'price' => '9000',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $product->refresh();
        $this->assertSame('Nouveau nom', $product->name);
        $this->assertSame('9000.00', $product->price);
    }

    public function test_admin_can_archive_a_product(): void
    {
        $product = Product::factory()->create(['status' => 'published']);

        $this->actingAs($this->admin())
            ->delete("/admin/products/{$product->id}")
            ->assertRedirect('/admin/products')
            ->assertSessionHas('success');

        $this->assertSame('archived', $product->fresh()->status);
        $this->assertFalse($product->fresh()->is_available);
    }

    public function test_admin_can_delete_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'À supprimer']);

        $this->actingAs($this->admin())
            ->delete("/admin/products/{$product->id}/delete")
            ->assertRedirect('/admin/products')
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNull(Product::find($product->id)); // exclu par le scope SoftDeletes
    }

    public function test_non_admin_cannot_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/admin/products/{$product->id}/delete")
            ->assertForbidden();

        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_admin_can_adjust_physical_stock_and_a_movement_is_logged(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->physical()->create(['stock_available' => 10]);

        $this->actingAs($admin)
            ->from("/admin/products/{$product->id}/edit")
            ->post("/admin/products/{$product->id}/stock", [
                'stock_available' => 25,
                'reason' => 'Réapprovisionnement fournisseur',
            ])
            ->assertRedirect("/admin/products/{$product->id}/edit")
            ->assertSessionHas('success');

        $this->assertSame(25, (int) $product->fresh()->stock_available);

        $movement = StockMovement::where('product_id', $product->id)->latest()->first();
        $this->assertNotNull($movement);
        $this->assertSame('restocked', $movement->type);
        $this->assertSame(15, $movement->quantity);
        $this->assertSame($admin->id, $movement->admin_id);
        $this->assertSame('Réapprovisionnement fournisseur', $movement->reason);
    }

    public function test_downward_stock_correction_is_logged_as_adjustment(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 40]);

        $this->actingAs($this->admin())
            ->post("/admin/products/{$product->id}/stock", ['stock_available' => 32, 'reason' => 'Casse inventaire'])
            ->assertSessionHas('success');

        $movement = StockMovement::where('product_id', $product->id)->latest()->first();
        $this->assertSame('adjustment', $movement->type);
        $this->assertSame(8, $movement->quantity);
    }

    public function test_stock_adjustment_is_rejected_for_non_physical_products(): void
    {
        $product = Product::factory()->create(['type' => 'digital']);

        $this->actingAs($this->admin())
            ->post("/admin/products/{$product->id}/stock", ['stock_available' => 5])
            ->assertStatus(422);
    }

    public function test_edit_screen_shows_stock_panel_and_movement_history(): void
    {
        $product = Product::factory()->physical()->create(['stock_available' => 12]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'restocked',
            'quantity' => 12,
            'reason' => 'Stock de départ',
        ]);

        $this->actingAs($this->admin())->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertSee('Stock')
            ->assertSee('Historique des mouvements')
            ->assertSee('Stock de départ');
    }

    public function test_admin_can_upload_a_main_image_when_creating_a_product(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', $this->digitalPayload([
            'main_image_file' => UploadedFile::fake()->image('cover.jpg', 800, 800),
        ]))->assertSessionHas('success');

        $product = Product::where('name', 'Ebook Croissance')->firstOrFail();

        $this->assertNotNull($product->main_image);
        $this->assertStringStartsWith('/uploads/products/', $product->main_image);
        $this->assertFileExists(public_path(ltrim($product->main_image, '/')));
    }

    public function test_admin_can_add_gallery_images_and_remove_one_on_update(): void
    {
        $product = Product::factory()->create([
            'gallery' => ['/uploads/products/old-a.jpg', '/uploads/products/old-b.jpg'],
        ]);

        $this->actingAs($this->admin())
            ->put("/admin/products/{$product->id}", $this->digitalPayload([
                'name' => $product->name,
                'slug' => $product->slug,
                'gallery_files' => [UploadedFile::fake()->image('new.jpg')],
                'gallery_remove' => ['/uploads/products/old-a.jpg'],
            ]))
            ->assertSessionHas('success');

        $gallery = $product->fresh()->gallery;

        $this->assertNotContains('/uploads/products/old-a.jpg', $gallery);
        $this->assertContains('/uploads/products/old-b.jpg', $gallery);
        $this->assertCount(2, $gallery); // old-b + la nouvelle
    }

    public function test_admin_can_remove_the_main_image_on_update(): void
    {
        $product = Product::factory()->create(['main_image' => '/uploads/products/keep.jpg']);

        $this->actingAs($this->admin())
            ->put("/admin/products/{$product->id}", $this->digitalPayload([
                'name' => $product->name,
                'slug' => $product->slug,
                'remove_main_image' => '1',
            ]))
            ->assertSessionHas('success');

        $this->assertNull($product->fresh()->main_image);
    }

    public function test_updating_without_touching_images_keeps_them(): void
    {
        $product = Product::factory()->create(['main_image' => '/uploads/products/keep.jpg']);

        $this->actingAs($this->admin())
            ->put("/admin/products/{$product->id}", $this->digitalPayload([
                'name' => 'Renommé',
                'slug' => $product->slug,
            ]))
            ->assertSessionHas('success');

        $this->assertSame('/uploads/products/keep.jpg', $product->fresh()->main_image);
    }

    /**
     * Cas réel : l'admin ne veut que RETIRER des images. Le navigateur envoie
     * quand même les <input type=file> vides (UPLOAD_ERR_NO_FILE) — la
     * validation ne doit pas les rejeter, et le retrait doit s'appliquer.
     */
    public function test_removing_images_works_with_empty_file_inputs_submitted(): void
    {
        $product = Product::factory()->create([
            'main_image' => '/uploads/products/main.jpg',
            'gallery' => ['/uploads/products/g1.jpg', '/uploads/products/g2.jpg'],
        ]);

        // Reproduit fidèlement un <input type="file"> laissé vide par le navigateur.
        $emptyFile = new UploadedFile('', '', null, UPLOAD_ERR_NO_FILE, true);

        $this->actingAs($this->admin())
            ->put("/admin/products/{$product->id}", $this->digitalPayload([
                'name' => $product->name,
                'slug' => $product->slug,
                'remove_main_image' => '1',
                'gallery_remove' => ['/uploads/products/g1.jpg'],
                'main_image_file' => $emptyFile,
                'gallery_files' => [$emptyFile],
            ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $fresh = $product->fresh();
        $this->assertNull($fresh->main_image);
        $this->assertSame(['/uploads/products/g2.jpg'], array_values($fresh->gallery));
    }

    public function test_gallery_accepts_webp_and_png_uploads(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/products/{$product->id}", $this->digitalPayload([
                'name' => $product->name,
                'slug' => $product->slug,
                'gallery_files' => [
                    UploadedFile::fake()->image('shot.webp'),
                    UploadedFile::fake()->image('shot.png'),
                ],
            ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertCount(2, $product->fresh()->gallery);
    }

    public function test_main_image_upload_rejects_a_non_image_file(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/products/create')
            ->post('/admin/products', $this->digitalPayload([
                'main_image_file' => UploadedFile::fake()->create('malware.pdf', 200, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('main_image_file');
    }

    public function test_service_edit_screen_shows_the_availability_panel(): void
    {
        $service = Product::factory()->service()->create();

        $this->actingAs($this->admin())->get("/admin/products/{$service->id}/edit")
            ->assertOk()
            ->assertSee('Calendrier')
            ->assertSee("Durée d'un rendez-vous", false)
            ->assertSee('Jours &amp; horaires disponibles', false)
            ->assertSee('Pauses &amp; jours indisponibles', false)
            ->assertSee('RDV en parallèle')
            ->assertSee('Ajouter un jour');
    }

    public function test_admin_sets_a_service_weekly_schedule_from_the_form(): void
    {
        $service = Product::factory()->service()->create();

        $this->actingAs($this->admin())
            ->from("/admin/products/{$service->id}/edit")
            ->put("/admin/services/{$service->id}/schedule", [
                'duration_minutes' => 45,
                'windows' => [
                    ['weekday' => 2, 'start_time' => '09:00', 'end_time' => '12:00', 'capacity' => 3],
                    ['weekday' => 4, 'start_time' => '14:00', 'end_time' => '18:00', 'capacity' => 1],
                ],
            ])
            ->assertRedirect("/admin/products/{$service->id}/edit")
            ->assertSessionHas('success');

        // §18 : durée, jours, horaires et « nb de rendez-vous en parallèle » (capacity).
        $this->assertSame(45, $service->fresh()->duration_minutes);
        $this->assertDatabaseHas('service_schedules', ['product_id' => $service->id, 'weekday' => 2, 'capacity' => 3]);
        $this->assertDatabaseHas('service_schedules', ['product_id' => $service->id, 'weekday' => 4, 'capacity' => 1]);
    }

    public function test_submitting_an_empty_schedule_clears_it(): void
    {
        $service = Product::factory()->service()->create();
        app(\App\Services\ServiceAvailabilityService::class)->setWeeklySchedule($service, [
            ['weekday' => 1, 'start_time' => '09:00', 'end_time' => '17:00', 'capacity' => 1],
        ]);

        $this->actingAs($this->admin())
            ->from("/admin/products/{$service->id}/edit")
            ->put("/admin/services/{$service->id}/schedule", [])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('service_schedules', 0);
    }

    public function test_admin_adds_then_removes_a_service_time_off_from_the_form(): void
    {
        $service = Product::factory()->service()->create();

        $this->actingAs($this->admin())
            ->from("/admin/products/{$service->id}/edit")
            ->post('/admin/services/time-off', [
                'product_id' => $service->id,
                'date' => '2026-12-25',
                'reason' => 'Jour férié',
            ])
            ->assertRedirect("/admin/products/{$service->id}/edit")
            ->assertSessionHas('success');

        $off = \App\Models\ServiceTimeOff::where('product_id', $service->id)->firstOrFail();
        $this->assertSame('Jour férié', $off->reason);

        $this->actingAs($this->admin())
            ->from("/admin/products/{$service->id}/edit")
            ->delete("/admin/services/time-off/{$off->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('service_time_off', ['id' => $off->id]);
    }

    public function test_non_admin_cannot_manage_service_availability(): void
    {
        $service = Product::factory()->service()->create();

        $this->actingAs(User::factory()->create())
            ->put("/admin/services/{$service->id}/schedule", ['windows' => []])
            ->assertForbidden();
    }

    public function test_admin_product_show_page_renders(): void
    {
        $product = Product::factory()->physical()->create([
            'name' => 'Sac Toile MALKUTHAR',
            'stock_available' => 9,
        ]);

        $this->actingAs($this->admin())->get("/admin/products/{$product->id}")
            ->assertOk()
            ->assertSee('Sac Toile MALKUTHAR')
            ->assertSee('Stock')
            ->assertSee('Disponible');
    }
}
