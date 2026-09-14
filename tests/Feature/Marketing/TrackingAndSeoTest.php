<?php

namespace Tests\Feature\Marketing;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §72-§75 — langue, capture UTM/attribution, SEO (sitemap), pixels.
 */
class TrackingAndSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_lang_query_switches_the_application_locale(): void
    {
        $this->get('/login?lang=en')->assertOk();
        $this->assertSame('en', app()->getLocale());

        $this->get('/login?lang=de')->assertOk(); // non supporté -> défaut
        $this->assertSame('fr', app()->getLocale());
    }

    public function test_utm_parameters_are_captured_and_persisted_on_the_order(): void
    {
        $product = Product::factory()->digitalAffiliable()->create(['price' => 10000]);
        $user = User::factory()->create();

        // 1re visite avec UTM (par ex. depuis une pub TikTok).
        $this->get('/?utm_source=tiktok&utm_medium=cpc&utm_campaign=ramadan');

        // 2e visite avec une autre source : first-touch conservé.
        $this->get('/?utm_source=facebook');

        $this->actingAs($user)->post('/checkout', [
            'payment_method' => 'mobile_money',
            'payment_provider' => 'fedapay',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $order = Order::first();
        $this->assertSame('tiktok', $order->utm_data['utm_source']);
        $this->assertSame('ramadan', $order->utm_data['utm_campaign']);
    }

    public function test_sitemap_lists_published_products_only(): void
    {
        $published = Product::factory()->create(['status' => 'published', 'is_available' => true, 'slug' => 'ebook-visible']);
        $draft = Product::factory()->create(['status' => 'draft', 'slug' => 'ebook-hidden']);

        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml');

        $response->assertSee('/produits/ebook-visible', false);
        $response->assertDontSee('/produits/ebook-hidden', false);
        $response->assertSee('/programme-affiliation/conditions', false);
    }

    public function test_marketing_pixels_render_only_when_configured(): void
    {
        $this->get('/login')->assertDontSee('fbq(\'init\'', false);

        config(['malkuthar.tracking.meta_pixel_id' => '1234567890']);

        $this->get('/login')->assertSee("fbq('init','1234567890')", false);
    }

    public function test_registration_rejects_an_unsupported_currency(): void
    {
        $this->post('/register', [
            'first_name' => 'A', 'last_name' => 'B',
            'email' => 'x@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'currency' => 'GBP',
        ])->assertSessionHasErrors('currency');
    }
}
