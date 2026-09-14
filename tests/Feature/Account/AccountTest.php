<?php

namespace Tests\Feature\Account;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * §77 — Espace client : téléchargements, commandes / suivi colis, rendez-vous,
 * profil.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    private function paidOrderFor(User $user, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id,
            'order_number' => 'TEST-'.Str::upper(Str::random(8)),
            'status' => 'processing',
            'payment_status' => 'paid',
            'currency' => 'XOF',
            'subtotal' => 10000,
            'total_amount' => 10000,
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/mon-compte')->assertRedirect('/login');
    }

    public function test_client_sees_their_dashboard(): void
    {
        $user = User::factory()->create(['first_name' => 'Awa']);

        $this->actingAs($user)->get('/mon-compte')
            ->assertOk()
            ->assertSee('Awa');
    }

    public function test_downloads_page_lists_paid_digital_items_with_signed_link(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['type' => 'digital', 'is_affiliable' => true]);
        $order = $this->paidOrderFor($user);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Ebook Alpha',
            'product_type' => 'digital',
            'is_affiliable_snapshot' => true,
            'quantity' => 1,
            'unit_price' => 10000,
            'discount_amount' => 0,
            'total_price' => 10000,
            'currency' => 'XOF',
            'commission_base_amount' => 10000,
        ]);

        $this->actingAs($user)->get('/mon-compte/telechargements')
            ->assertOk()
            ->assertSee('Ebook Alpha')
            ->assertSee('/products/'.$order->items()->first()->id.'/download')
            ->assertSee('signature=', false);
    }

    public function test_downloads_page_hides_items_from_unpaid_orders(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['type' => 'digital']);
        $order = $this->paidOrderFor($user, ['payment_status' => 'pending']);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Ebook Secret',
            'product_type' => 'digital',
            'is_affiliable_snapshot' => false,
            'quantity' => 1,
            'unit_price' => 10000,
            'discount_amount' => 0,
            'total_price' => 10000,
            'currency' => 'XOF',
            'commission_base_amount' => null,
        ]);

        $this->actingAs($user)->get('/mon-compte/telechargements')
            ->assertOk()
            ->assertDontSee('Ebook Secret');
    }

    public function test_orders_list_and_detail(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['type' => 'physical']);
        $order = $this->paidOrderFor($user, [
            'has_physical_items' => true,
            'shipping_status' => 'shipped',
            'shipping_carrier' => 'DHL',
            'tracking_number' => 'TRK-999',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Mug Malkuthar',
            'product_type' => 'physical',
            'is_affiliable_snapshot' => false,
            'quantity' => 2,
            'unit_price' => 5000,
            'discount_amount' => 0,
            'total_price' => 10000,
            'currency' => 'XOF',
            'commission_base_amount' => null,
        ]);

        $this->actingAs($user)->get('/mon-compte/commandes')
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAs($user)->get('/mon-compte/commandes/'.$order->id)
            ->assertOk()
            ->assertSee('Mug Malkuthar')
            ->assertSee('TRK-999')
            ->assertSee('Suivi de livraison');
    }

    public function test_client_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $order = $this->paidOrderFor($owner);

        $this->actingAs($intruder)->get('/mon-compte/commandes/'.$order->id)
            ->assertForbidden();
    }

    public function test_appointments_page_separates_upcoming_and_past(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['type' => 'service']);
        $order = $this->paidOrderFor($user, ['has_service_items' => true]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Coaching Futur',
            'product_type' => 'service',
            'is_affiliable_snapshot' => false,
            'quantity' => 1,
            'unit_price' => 10000,
            'discount_amount' => 0,
            'total_price' => 10000,
            'currency' => 'XOF',
            'commission_base_amount' => null,
            'reservation_datetime' => now()->addWeek(),
        ]);

        $this->actingAs($user)->get('/mon-compte/rendez-vous')
            ->assertOk()
            ->assertSee('Coaching Futur');
    }

    public function test_client_can_update_their_profile(): void
    {
        $user = User::factory()->create(['first_name' => 'Old', 'phone' => null]);

        $this->actingAs($user)
            ->from('/mon-compte/profil')
            ->put('/mon-compte/profil', [
                'first_name' => 'New',
                'last_name' => $user->last_name,
                'phone' => '+22990000000',
                'country' => 'BJ',
            ])
            ->assertRedirect('/mon-compte/profil')
            ->assertSessionHas('success');

        $this->assertSame('New', $user->fresh()->first_name);
        $this->assertSame('+22990000000', $user->fresh()->phone);
    }
}
