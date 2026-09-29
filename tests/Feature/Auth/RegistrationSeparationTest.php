<?php

namespace Tests\Feature\Auth;

use App\Models\Marketplace\VendorProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('marketplace.enabled', '1');
    }

    public function test_client_registration_page_is_accessible_and_independent(): void
    {
        $response = $this->get(route('register.client'))->assertOk();
        $response->assertSee('Créer mon compte Client');
        $response->assertDontSee('Choisissez votre type de compte');

        $this->get('/register')->assertOk();
    }

    public function test_client_registration_creates_account_and_attaches_system_sponsor_by_default(): void
    {
        $response = $this->post(route('register.client.store'), [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean.dupont@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $user = User::where('email', 'jean.dupont@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('client', $user->role);
        $this->assertAuthenticatedAs($user);

        // Parrainé d'office par le compte Système
        $this->assertNotNull($user->parent_id);
        $this->assertSame('system@malkuthar.com', $user->parent->email);
    }

    public function test_affiliate_registration_page_is_accessible_and_independent(): void
    {
        $response = $this->get(route('register.affiliate'))->assertOk();
        $response->assertSee('Devenir Partenaire Affilié');
        $response->assertDontSee('Choisissez votre type de compte');
    }

    public function test_affiliate_registration_creates_account_and_redirects_to_affiliate_dashboard(): void
    {
        $response = $this->post(route('register.affiliate.store'), [
            'first_name' => 'Alice',
            'last_name' => 'Affiliee',
            'email' => 'alice@example.com',
            'phone' => '+22997000001',
            'country' => 'Bénin',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $user = User::where('email', 'alice@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->parent_id);

        $response->assertRedirect(route('affiliate.dashboard'));
    }

    public function test_vendor_registration_page_is_accessible_and_clearly_decoupled_from_product_creation(): void
    {
        $response = $this->get(route('register.vendor'))->assertOk();
        $response->assertSee('Créer mon compte Vendeur');
        $response->assertDontSee('Choisissez votre type de compte');

        // Explicitement séparé de la création de produit
        $response->assertSee("Vous n'avez aucun produit à enregistrer maintenant", false);
        $response->assertSee("Étape 1 : Création de votre compte Vendeur uniquement", false);

        $this->get(route('marketplace.become-vendor'))->assertOk();
    }

    public function test_vendor_registration_creates_account_and_pending_vendor_profile(): void
    {
        $response = $this->post(route('register.vendor.store'), [
            'first_name' => 'Marc',
            'last_name' => 'Vendeur',
            'email' => 'marc.vendeur@example.com',
            'phone' => '+22997000002',
            'country' => 'Bénin',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'display_name' => 'Atelier Bien-Être Marc',
            'legal_name' => 'Marc SARL',
            'bio' => 'Création artisanale de produits mystiques et huiles essentielles bio.',
            'region' => 'Littoral',
            'city' => 'Cotonou',
            'accept_terms' => '1',
        ]);

        $user = User::where('email', 'marc.vendeur@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        // Profil vendeur créé en statut pending (validation manuelle obligatoire)
        $profile = $user->vendorProfile;
        $this->assertNotNull($profile);
        $this->assertSame('pending', $profile->status);
        $this->assertSame('Atelier Bien-Être Marc', $profile->display_name);

        // Redirigé vers la page de statut
        $response->assertRedirect(route('vendor.application.status'));

        // Bloqué sur l'espace vendeur et bloqué sur la création de produit tant que pending
        $this->get(route('vendor.dashboard'))->assertForbidden();
        $this->get(route('vendor.offers.create'))->assertForbidden();
    }

    public function test_admin_manually_approves_pending_vendor_unlocking_product_creation(): void
    {
        $admin = User::factory()->admin()->create();
        $vendorUser = User::factory()->create();
        $profile = VendorProfile::factory()->create(['user_id' => $vendorUser->id, 'status' => 'pending']);

        // Tentative de création de produit par le vendeur avant approbation -> Interdit
        $this->actingAs($vendorUser)
            ->get(route('vendor.offers.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.marketplace.vendors.approve', $profile))
            ->assertSessionHas('success');

        $profile->refresh();
        $vendorUser->refresh();
        $this->assertSame('active', $profile->status);
        $this->assertSame($admin->id, $profile->approved_by);
        $this->assertNotNull($profile->approved_at);

        // Maintenant le vendeur a accès à son dashboard ET peut créer des produits
        $this->actingAs($vendorUser)
            ->get(route('vendor.dashboard'))
            ->assertOk();

        $this->actingAs($vendorUser)
            ->get(route('vendor.offers.create'))
            ->assertOk();
    }
}
