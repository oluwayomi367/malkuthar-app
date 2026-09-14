<?php

namespace Tests\Feature\Storefront;

use App\Mail\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StaticPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_and_contact_pages_load(): void
    {
        $this->get('/a-propos')->assertOk()->assertSee('trois métiers');
        $this->get('/contact')->assertOk()->assertSee('Envoyer le message');
    }

    public function test_affiliation_page_explains_the_program_and_links_to_terms(): void
    {
        $this->get('/affiliation')
            ->assertOk()
            ->assertSee('Comment ça marche')
            ->assertSee('Du clic au versement')
            ->assertSee(route('affiliate.program-terms'));
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
            ->assertSee('Rejoindre la liste d\'attente', false)
            ->assertSee('Comment ça marchera')
            ->assertSee('name="subject" value="vendeur"', false)
            ->assertSee(route('contact.store'));
    }

    public function test_home_page_teases_the_marketplace(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('place de marché', false)
            ->assertSee(route('marketplace'));
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
