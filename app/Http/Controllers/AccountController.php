<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

/**
 * §77 — Espace client : téléchargements, suivi des colis, rendez-vous,
 * factures, profil.
 */
class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        return view('account.dashboard', [
            'downloads' => $this->digitalItems($user->id)->take(4),
            'shippingOrders' => Order::query()
                ->where('user_id', $user->id)
                ->where('has_physical_items', true)
                ->where('payment_status', 'paid')
                ->latest()
                ->limit(3)
                ->get(),
            'appointments' => $this->serviceItems($user->id, upcomingOnly: true)->take(3),
        ]);
    }

    public function downloads(Request $request): View
    {
        return view('account.downloads', [
            'items' => $this->digitalItems($request->user()->id),
        ]);
    }

    public function orders(Request $request): View
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->withCount('items')
            ->latest()
            ->paginate(15);

        return view('account.orders.index', compact('orders'));
    }

    public function order(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $order->load('items');

        return view('account.orders.show', compact('order'));
    }

    public function appointments(Request $request): View
    {
        $items = $this->serviceItems($request->user()->id);

        return view('account.appointments', [
            'upcoming' => $items->filter(fn ($i) => $i->reservation_datetime?->isFuture())->values(),
            'past' => $items->filter(fn ($i) => $i->reservation_datetime?->isPast())->values(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }

    /**
     * Lignes digitales des commandes payées du client, avec lien de
     * téléchargement signé (§9).
     */
    private function digitalItems(string $userId)
    {
        return OrderItem::query()
            ->where('product_type', 'digital')
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId)->where('payment_status', 'paid'))
            ->with('order:id,order_number,created_at')
            ->latest()
            ->get()
            ->each(function (OrderItem $item) {
                $item->download_url = URL::temporarySignedRoute(
                    'products.download',
                    now()->addDays(7),
                    ['orderItem' => $item->id],
                );
                $item->max_downloads = (int) config('malkuthar.digital_delivery.max_downloads', 5);
            });
    }

    private function serviceItems(string $userId, bool $upcomingOnly = false)
    {
        return OrderItem::query()
            ->where('product_type', 'service')
            ->whereNotNull('reservation_datetime')
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId)->where('payment_status', 'paid'))
            ->when($upcomingOnly, fn ($q) => $q->where('reservation_datetime', '>=', now()))
            ->with('product:id,name,location,delivery_mode,meeting_link,duration_minutes')
            ->orderBy('reservation_datetime')
            ->get();
    }
}
