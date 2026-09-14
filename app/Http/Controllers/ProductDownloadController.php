<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * §9 — Livraison sécurisée des produits digitaux : lien temporaire signé
 * (généré par DigitalDeliveryService), commande payée obligatoire,
 * nombre de téléchargements limité, fichier jamais exposé publiquement
 * (servi depuis le disque `local`, pas `public`).
 */
class ProductDownloadController extends Controller
{
    public function __invoke(Request $request, OrderItem $orderItem): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'Ce lien de téléchargement est invalide ou a expiré.');
        abort_unless($orderItem->product_type === 'digital', 404);

        $order = $orderItem->order;
        abort_unless($order && $order->payment_status === 'paid', 403, 'Cette commande n\'est pas payée.');

        $maxDownloads = (int) config('malkuthar.digital_delivery.max_downloads', 5);
        if ($maxDownloads > 0 && $orderItem->download_count >= $maxDownloads) {
            abort(403, 'Limite de téléchargements atteinte pour ce produit.');
        }

        $product = $orderItem->product;
        abort_unless($product && $product->file_path, 404);
        abort_unless(Storage::disk('local')->exists($product->file_path), 404);

        $orderItem->increment('download_count');

        return Storage::disk('local')->download($product->file_path, $product->name);
    }
}
