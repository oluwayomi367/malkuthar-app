<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\StorefrontImages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Images de la vitrine remplaçables depuis l'administration.
 */
class AdminStorefrontImagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_cannot_manage_storefront_images(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get('/admin/vitrine/images')->assertForbidden();
        $this->actingAs($client)
            ->post('/admin/vitrine/images/home.hero', ['image' => UploadedFile::fake()->create('a.jpg', 10)])
            ->assertForbidden();
    }

    public function test_admin_sees_every_slot_with_its_original_image(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/vitrine/images')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Storefront/Images')
                ->has('groups'));

        $this->assertStringEndsWith('/images/hero-craft.jpg', StorefrontImages::url('home.hero'));
    }

    public function test_uploaded_image_replaces_the_slot_everywhere_and_can_be_reset(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/vitrine/images/home.hero', ['image' => UploadedFile::fake()->create('banniere.png', 50)])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $custom = StorefrontImages::custom('home.hero');
        $this->assertNotNull($custom);
        $this->assertStringStartsWith(StorefrontImages::UPLOAD_PREFIX, $custom);
        $this->assertFileExists(public_path(ltrim($custom, '/')));

        // Les pages de la vitrine reçoivent la nouvelle URL.
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('images', fn ($images) => str_ends_with($images['home.hero'], $custom)));

        $this->actingAs($admin)->delete('/admin/vitrine/images/home.hero')->assertRedirect();

        $this->assertNull(StorefrontImages::custom('home.hero'));
        $this->assertFileDoesNotExist(public_path(ltrim($custom, '/')));
    }

    public function test_upload_rejects_non_image_files_and_unknown_slots(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/vitrine/images/home.hero', ['image' => UploadedFile::fake()->create('notes.pdf', 10)])
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)
            ->post('/admin/vitrine/images/inconnu', ['image' => UploadedFile::fake()->create('a.jpg', 10)])
            ->assertNotFound();
    }
}
