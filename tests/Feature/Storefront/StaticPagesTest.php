<?php

namespace Tests\Feature\Storefront;

use App\Mail\ContactMessage;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Vitrine publique rendue via Inertia/React (voir resources/js/Pages) — les
 * assertions portent sur les props transmises au composant, pas sur du HTML
 * rendu côté serveur (pas de SSR ici : React hydrate côté client).
 */
class StaticPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_and_contact_pages_load(): void
    {
        $this->get('/a-propos')->assertOk()->assertInertia(fn (Assert $page) => $page->component('About'));

        $this->get('/contact')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Contact')
            ->where('contact.email', config('malkuthar.contact.email')));
    }

    public function test_affiliation_page_explains_the_program_and_links_to_terms(): void
    {
        $this->get('/affiliation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Affiliation')
                ->where('rateL1', 15)
                ->where('rateL2', 10)
                ->where('rateL3', 5)
                ->has('validationDelayDays'));
    }

    public function test_affiliation_page_shows_a_real_affiliable_product_with_its_computed_gain(): void
    {
        $product = Product::factory()->digitalAffiliable()->create(['name' => 'Guide de méditation', 'price' => 10000]);

        $this->get('/affiliation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('showcase.0.name', 'Guide de méditation')
                ->where('showcase.0.slug', $product->slug)
                ->where('showcase.0.gain', 1500));
    }

    public function test_affiliation_page_is_separate_from_the_terms_page(): void
    {
        $this->get('/programme-affiliation/conditions')
            ->assertOk()
            ->assertSee('Conditions du programme', false)
            ->assertSee('Fonctionnement')
            ->assertSee(route('affiliation'));
    }

    public function test_marketplace_landing_page_loads_with_waitlist_form(): void
    {
        $this->get('/marketplace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Marketplace/Landing')
                ->where('enabled', false)
                ->where('stats', null));
    }

    public function test_marketplace_landing_page_shows_pricing_tiers_once_live(): void
    {
        Setting::put('marketplace.enabled', '1');

        $this->get('/marketplace')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('enabled', true)
                ->has('stats')
                ->where('starterRate', 15)
                ->where('growthRate', 10));
    }

    public function test_home_page_teases_the_marketplace(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Home')->where('marketplaceEnabled', false));
    }

    public function test_marketplace_vendor_terms_page_loads(): void
    {
        $this->get('/conditions-vendeurs')
            ->assertOk()
            ->assertSee('Conditions du programme vendeur', false)
            ->assertSee('Commission de la plateforme');
    }

    public function test_marketplace_waitlist_submission_is_labelled_as_a_vendor_request(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Koffi B.',
            'email' => 'koffi@example.com',
            'subject' => 'vendeur',
            'message' => 'Je vends des coffrets artisanaux à Porto-Novo et je souhaite rejoindre.',
        ])->assertRedirect()->assertSessionHas('success');

        Mail::assertQueued(ContactMessage::class, fn ($m) => $m->senderEmail === 'koffi@example.com'
            && $m->subjectLabel === 'Devenir vendeur (Marketplace)');
    }

    public function test_contact_form_sends_a_message(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Awa D.',
            'email' => 'awa@example.com',
            'subject' => 'affiliation',
            'message' => 'Bonjour, comment devient-on affilié ?',
        ])->assertRedirect()->assertSessionHas('success');

        Mail::assertQueued(ContactMessage::class, fn ($m) => $m->senderEmail === 'awa@example.com'
            && $m->subjectLabel === "Le programme d'affiliation");
    }

    public function test_contact_form_validates_input(): void
    {
        $this->post('/contact', ['name' => '', 'email' => 'nope', 'subject' => 'x', 'message' => 'court'])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_contact_honeypot_blocks_bots(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'name' => 'Bot', 'email' => 'bot@example.com', 'subject' => 'autre',
            'message' => 'spam spam spam spam', 'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        Mail::assertNothingQueued();
    }
}
