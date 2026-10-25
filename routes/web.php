<?php

use App\Http\Controllers\Admin\AffiliateController as AdminAffiliateController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CommissionCampaignController as AdminCommissionCampaignController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductVariantController as AdminProductVariantController;
use App\Http\Controllers\Admin\ServiceBookingController as AdminServiceBookingController;
use App\Http\Controllers\Admin\ServiceScheduleController;
use App\Http\Controllers\Admin\ServiceTimeOffController;
use App\Http\Controllers\Admin\Marketplace\CategoryController as AdminMarketplaceCategoryController;
use App\Http\Controllers\Admin\Marketplace\CategoryFieldController as AdminMarketplaceCategoryFieldController;
use App\Http\Controllers\Admin\Marketplace\OfferModerationController as AdminMarketplaceOfferController;
use App\Http\Controllers\Admin\Marketplace\SettingsController as AdminMarketplaceSettingsController;
use App\Http\Controllers\Admin\Marketplace\VendorController as AdminMarketplaceVendorController;
use App\Http\Controllers\Admin\WalletAdjustmentController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Vendor\OfferController as VendorOfferController;
use App\Http\Controllers\Vendor\VendorApplicationController;
use App\Http\Controllers\Vendor\VendorDashboardController;
use App\Http\Controllers\Vendor\VendorProfileController;
use App\Http\Controllers\AffiliateActivationController;
use App\Http\Controllers\AffiliateDashboardController;
use App\Http\Controllers\AffiliateProgramTermsController;
use App\Http\Controllers\AffiliationController;
use App\Http\Controllers\AffiliateReportsController;
use App\Http\Controllers\AffiliateWithdrawalMethodController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CheckoutReturnController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProductDownloadController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ServiceAvailabilityController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

// §73-§74 — vitrine publique
Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/boutique', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/produits/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::inertia('/panier', 'Cart')->name('cart');
Route::inertia('/a-propos', 'About')->name('about');
Route::get('/marketplace', App\Http\Controllers\Marketplace\MarketplaceLandingController::class)->name('marketplace');
Route::view('/conditions-vendeurs', 'legal.marketplace-vendor-terms')->name('marketplace.vendor-terms');

// Pages légales
Route::view('/mentions-legales', 'legal.notice')->name('legal.notice');
Route::view('/confidentialite', 'legal.privacy')->name('legal.privacy');
Route::view('/cookies', 'legal.cookies')->name('legal.cookies');

// Boutique publique de la place de marché — masquée tant que le module est
// désactivé (middleware `marketplace`).
Route::middleware('marketplace')->prefix('place-de-marche')->group(function () {
    Route::controller(App\Http\Controllers\Marketplace\MarketplaceShopController::class)->name('marketplace.shop')->group(function () {
        Route::get('/', 'index')->name('');
        Route::get('/o/{slug}', 'offer')->name('.offer');
        Route::get('/v/{slug}', 'vendor')->name('.vendor');
    });

    // Panier (session)
    Route::controller(App\Http\Controllers\Marketplace\CartController::class)->group(function () {
        Route::get('/panier', 'show')->name('marketplace.cart');
        Route::post('/panier', 'add')->name('marketplace.cart.add');
        Route::patch('/panier', 'update')->name('marketplace.cart.update');
        Route::delete('/panier', 'remove')->name('marketplace.cart.remove');
    });

    // Commande
    Route::controller(App\Http\Controllers\Marketplace\CheckoutController::class)->group(function () {
        Route::get('/commander', 'show')->name('marketplace.checkout');
        Route::post('/commander', 'store')->name('marketplace.checkout.store');
        Route::get('/commande/{number}', 'confirmation')->name('marketplace.checkout.confirmation');
    });

    // Avis acheteur (compte requis + preuve d'achat)
    Route::post('/avis', [App\Http\Controllers\Marketplace\ReviewController::class, 'store'])
        ->middleware('auth')->name('marketplace.reviews.store');
});
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// §74 — SEO
Route::get('/sitemap.xml', [SitemapController::class, 'firstParty'])->name('sitemap');
Route::get('/sitemap-marketplace.xml', [SitemapController::class, 'marketplace'])
    ->middleware('marketplace')->name('sitemap.marketplace');

// Aides de développement local (simuler un paiement / un remboursement sans PSP)
if (app()->environment('local')) {
    Route::post('/dev/orders/{order}/confirm-payment', [DevController::class, 'confirmPayment'])->name('dev.confirm-payment');
    Route::post('/dev/orders/{order}/refund', [DevController::class, 'refund'])->name('dev.refund');
    Route::view('/dev/styleguide', 'dev.styleguide')->name('dev.styleguide');
}

/*
|--------------------------------------------------------------------------
| Lien de parrainage (§33) — /ref/{code} pose le cookie d'attribution
|--------------------------------------------------------------------------
*/
Route::get('/ref/{code}', [ReferralController::class, 'capture'])->name('referral.capture');

// Page publique « Programme d'affiliation » — explique le fonctionnement.
Route::get('/affiliation', AffiliationController::class)->name('affiliation');

// §81 — page publique des conditions (contrat) du programme d'affiliation
Route::get('/programme-affiliation/conditions', AffiliateProgramTermsController::class)
    ->name('affiliate.program-terms');

/*
|--------------------------------------------------------------------------
| Authentification (§30, §77) — compte unique client/affilié
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Inscription Client (défaut)
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::get('/inscription', [RegisteredUserController::class, 'create'])->name('register.client');
    Route::get('/inscription/client', [RegisteredUserController::class, 'create']);
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.client.store');
    Route::post('/inscription/client', [RegisteredUserController::class, 'store']);

    // Inscription Affilié
    Route::get('/inscription/affilie', [RegisteredUserController::class, 'createAffiliate'])->name('register.affiliate');
    Route::post('/inscription/affilie', [RegisteredUserController::class, 'storeAffiliate'])->name('register.affiliate.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Inscription Vendeur (accessible à tous : aiguille intelligemment les membres connectés)
Route::get('/inscription/vendeur', [App\Http\Controllers\Marketplace\VendorRegistrationController::class, 'create'])->name('register.vendor');
Route::get('/devenir-vendeur', [App\Http\Controllers\Marketplace\VendorRegistrationController::class, 'create'])->name('marketplace.become-vendor');
Route::post('/inscription/vendeur', [App\Http\Controllers\Marketplace\VendorRegistrationController::class, 'store'])->name('register.vendor.store');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Commerce
|--------------------------------------------------------------------------
*/
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/return/{order}', CheckoutReturnController::class)->name('checkout.return');

// §9 — lien de téléchargement temporaire, signé, sans authentification
// requise (envoyé par email, y compris aux acheteurs invités).
Route::get('/products/{orderItem}/download', ProductDownloadController::class)
    ->middleware('signed')
    ->name('products.download');

// §17 — créneaux disponibles pour un service à une date donnée.
Route::get('/services/{product}/availability', [ServiceAvailabilityController::class, 'slots'])
    ->name('services.availability');

/*
|--------------------------------------------------------------------------
| Webhooks PSP (§22, §26, §66) — point d'entrée unique multi-PSP, exclu
| de la vérification CSRF dans bootstrap/app.php. Jamais de middleware `auth`.
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/{provider}', PaymentWebhookController::class)
    ->name('webhooks.psp')
    ->where('provider', '[a-z0-9_-]+');

/*
|--------------------------------------------------------------------------
| Espace affilié (§30, §43-§46) — nécessite un compte authentifié
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Adresses courtes des espaces : /espace mène à l'espace principal du compte.
    Route::get('/espace', fn () => redirect(\App\Support\ProfileHome::url(auth()->user())))->name('espace');
    Route::redirect('/espace/client', '/mon-compte');
    Route::redirect('/espace/affilie', '/affiliate/dashboard');
    Route::redirect('/espace/vendeur', '/vendeur');

    // §77 — Espace client
    Route::get('/mon-compte',[AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/mon-compte/telechargements', [AccountController::class, 'downloads'])->name('account.downloads');
    Route::get('/mon-compte/commandes', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/mon-compte/commandes/{order}', [AccountController::class, 'order'])->name('account.orders.show');
    Route::get('/mon-compte/rendez-vous', [AccountController::class, 'appointments'])->name('account.appointments');
    Route::get('/mon-compte/profil', [AccountController::class, 'profile'])->name('account.profile');
    Route::put('/mon-compte/profil', [AccountController::class, 'updateProfile'])->name('account.profile.update');

    // §30 — "Devenir affilié"
    Route::get('/affiliate/join', [AffiliateActivationController::class, 'show'])->name('affiliate.join');
    Route::post('/affiliate/join', [AffiliateActivationController::class, 'store'])->name('affiliate.activate');

    Route::get('/affiliate/dashboard', [AffiliateDashboardController::class, 'index'])
        ->name('affiliate.dashboard');

    Route::post('/affiliate/withdrawals', [WithdrawalController::class, 'requestWithdrawal'])
        ->name('affiliate.withdrawals.store');

    // §45-§47 — Mes ventes / Ma lignée / Mes gains
    Route::get('/affiliate/sales', [AffiliateReportsController::class, 'sales'])->name('affiliate.sales');
    Route::get('/affiliate/lineage', [AffiliateReportsController::class, 'lineage'])->name('affiliate.lineage');
    Route::get('/affiliate/earnings', [AffiliateReportsController::class, 'earnings'])->name('affiliate.earnings');

    // §56 - Nouveau numéro -> OTP -> Confirmation -> Enregistrement
    Route::post('/affiliate/withdrawal-method', [AffiliateWithdrawalMethodController::class, 'store'])
        ->name('affiliate.withdrawal-method.store');
    Route::post('/affiliate/withdrawal-method/confirm', [AffiliateWithdrawalMethodController::class, 'confirm'])
        ->name('affiliate.withdrawal-method.confirm');
});

/*
|--------------------------------------------------------------------------
| MARKETPLACE — espace vendeur (§ module Marketplace). Tout est masqué tant
| que le Setting `marketplace.enabled` est faux (middleware `marketplace`).
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'marketplace'])->prefix('vendeur')->name('vendor.')->group(function () {
    // Candidature — accessible à tout compte authentifié sans profil vendeur.
    Route::get('/devenir', [VendorApplicationController::class, 'show'])->name('application');
    Route::post('/devenir', [VendorApplicationController::class, 'store'])->name('application.store');
    Route::get('/candidature', [VendorApplicationController::class, 'status'])->name('application.status');

    // Espace vendeur — profil vendeur ACTIF requis.
    Route::middleware('vendor')->group(function () {
        Route::get('/', VendorDashboardController::class)->name('dashboard');
        Route::get('/boutique', [VendorProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/boutique', [VendorProfileController::class, 'update'])->name('profile.update');

        // Offres
        Route::get('/offres', [VendorOfferController::class, 'index'])->name('offers.index');
        Route::get('/offres/nouvelle', [VendorOfferController::class, 'create'])->name('offers.create');
        Route::post('/offres', [VendorOfferController::class, 'store'])->name('offers.store');
        Route::get('/offres/{offer}', [VendorOfferController::class, 'edit'])->name('offers.edit');
        Route::put('/offres/{offer}', [VendorOfferController::class, 'update'])->name('offers.update');
        Route::delete('/offres/{offer}', [VendorOfferController::class, 'destroy'])->name('offers.destroy');
        Route::post('/offres/{offer}/soumettre', [VendorOfferController::class, 'submit'])->name('offers.submit');
        Route::post('/offres/{offer}/retirer', [VendorOfferController::class, 'withdraw'])->name('offers.withdraw');
        Route::post('/offres/{offer}/publier', [VendorOfferController::class, 'publish'])->name('offers.publish');
        Route::post('/offres/{offer}/depublier', [VendorOfferController::class, 'unpublish'])->name('offers.unpublish');

        // Commandes reçues + traitement
        Route::get('/commandes', [App\Http\Controllers\Vendor\OrderController::class, 'index'])->name('orders.index');
        Route::get('/commandes/{suborder}', [App\Http\Controllers\Vendor\OrderController::class, 'show'])->name('orders.show');
        Route::post('/commandes/{suborder}/expedier', [App\Http\Controllers\Vendor\OrderController::class, 'ship'])->name('orders.ship');
        Route::post('/commandes/{suborder}/statut', [App\Http\Controllers\Vendor\OrderController::class, 'advance'])->name('orders.advance');
        Route::post('/commandes/{suborder}/lignes/{item}/confirmer', [App\Http\Controllers\Vendor\OrderController::class, 'confirmBooking'])->name('orders.confirm-booking');
        Route::post('/avis/{review}/repondre', [App\Http\Controllers\Vendor\OrderController::class, 'replyReview'])->name('reviews.reply');

        // Revenus & versements
        Route::get('/revenus', [App\Http\Controllers\Vendor\SettlementController::class, 'index'])->name('settlement.index');
        Route::get('/versements', [App\Http\Controllers\Vendor\PayoutController::class, 'index'])->name('payouts.index');
        Route::post('/versements', [App\Http\Controllers\Vendor\PayoutController::class, 'store'])->name('payouts.store');
        Route::post('/versements/moyens', [App\Http\Controllers\Vendor\PayoutController::class, 'storeAccount'])->name('payouts.accounts.store');
        Route::post('/versements/moyens/{account}/defaut', [App\Http\Controllers\Vendor\PayoutController::class, 'makeDefaultAccount'])->name('payouts.accounts.default');
        Route::delete('/versements/moyens/{account}', [App\Http\Controllers\Vendor\PayoutController::class, 'destroyAccount'])->name('payouts.accounts.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Administration (§18, §60-§64) — réservé role=admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::put('/services/{product}/schedule', [ServiceScheduleController::class, 'update'])
        ->name('services.schedule.update');

    Route::post('/services/time-off', [ServiceTimeOffController::class, 'store'])
        ->name('services.time-off.store');
    Route::delete('/services/time-off/{timeOff}', [ServiceTimeOffController::class, 'destroy'])
        ->name('services.time-off.destroy');

    // §18-§19 — calendrier : agenda des rendez-vous, déplacement, annulation
    Route::get('/calendar', [AdminServiceBookingController::class, 'index'])->name('calendar.index');
    Route::post('/calendar/{orderItem}/reschedule', [AdminServiceBookingController::class, 'reschedule'])->name('calendar.reschedule');
    Route::post('/calendar/{orderItem}/cancel', [AdminServiceBookingController::class, 'cancel'])->name('calendar.cancel');

    // §60
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Images de la vitrine (bannières, tuiles, photos des pages publiques)
    Route::get('/vitrine/images', [App\Http\Controllers\Admin\StorefrontImageController::class, 'index'])->name('storefront-images.index');
    Route::post('/vitrine/images/{key}', [App\Http\Controllers\Admin\StorefrontImageController::class, 'update'])->name('storefront-images.update');
    Route::delete('/vitrine/images/{key}', [App\Http\Controllers\Admin\StorefrontImageController::class, 'destroy'])->name('storefront-images.destroy');

    // §6-§18 — catalogue : produits + gestion du stock
    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::delete('/products/{product}/delete', [AdminProductController::class, 'delete'])->name('products.delete');
    Route::post('/products/{product}/stock', [AdminProductController::class, 'adjustStock'])->name('products.stock.adjust');

    // §12 — variantes des produits physiques
    Route::put('/products/{product}/variants', [AdminProductVariantController::class, 'sync'])->name('products.variants.sync');
    Route::post('/products/variants/{variant}/stock', [AdminProductVariantController::class, 'adjustStock'])->name('products.variants.stock');

    // §13, §22, §26 — commandes : suivi du cycle de vie + encaissement COD
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/fulfilment', [AdminOrderController::class, 'updateFulfilment'])->name('orders.fulfilment');
    Route::post('/orders/{order}/collect-cod', [AdminOrderController::class, 'collectCod'])->name('orders.collect-cod');
    Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');

    // §61-§62
    Route::get('/affiliates', [AdminAffiliateController::class, 'index'])->name('affiliates.index');
    Route::get('/affiliates/{user}', [AdminAffiliateController::class, 'show'])->name('affiliates.show');
    Route::post('/affiliates/{user}/suspend', [AdminAffiliateController::class, 'suspend'])->name('affiliates.suspend');
    Route::post('/affiliates/{user}/reactivate', [AdminAffiliateController::class, 'reactivate'])->name('affiliates.reactivate');

    // §49
    Route::post('/affiliates/{user}/wallet-adjustments', [WalletAdjustmentController::class, 'store'])
        ->name('affiliates.wallet-adjustments.store');

    // §54, §58 — retraits
    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals/{withdrawal}/mark-paid', [AdminWithdrawalController::class, 'markPaid'])->name('withdrawals.mark-paid');
    Route::post('/withdrawals/{withdrawal}/mark-failed', [AdminWithdrawalController::class, 'markFailed'])->name('withdrawals.mark-failed');

    // §68
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // §27 — taux de commission configurables (campagnes)
    Route::get('/commission-campaigns', [AdminCommissionCampaignController::class, 'index'])->name('commission-campaigns.index');
    Route::put('/commission-campaigns/validation-delay', [AdminCommissionCampaignController::class, 'updateValidationDelay'])->name('commission-campaigns.validation-delay');
    Route::post('/commission-campaigns', [AdminCommissionCampaignController::class, 'store'])->name('commission-campaigns.store');
    Route::put('/commission-campaigns/{campaign}', [AdminCommissionCampaignController::class, 'update'])->name('commission-campaigns.update');
    Route::delete('/commission-campaigns/{campaign}', [AdminCommissionCampaignController::class, 'destroy'])->name('commission-campaigns.destroy');

    // §79
    Route::get('/coupons', [CouponController::class, 'index'])->name('coupons.index');
    Route::post('/coupons', [CouponController::class, 'store'])->name('coupons.store');
    Route::put('/coupons/{coupon}', [CouponController::class, 'update'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('coupons.destroy');
});

/*
|--------------------------------------------------------------------------
| MARKETPLACE — administration (comptes vendeurs, catalogue, modération,
| commissions, versements). Réservé role=admin.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin/marketplace')->name('admin.marketplace.')->group(function () {
    Route::get('/settings', [AdminMarketplaceSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminMarketplaceSettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/announcement', [AdminMarketplaceSettingsController::class, 'updateAnnouncement'])->name('settings.announcement.update');

    Route::get('/vendors', [AdminMarketplaceVendorController::class, 'index'])->name('vendors.index');
    Route::get('/vendors/{vendor}', [AdminMarketplaceVendorController::class, 'show'])->name('vendors.show');
    Route::post('/vendors/{vendor}/approve', [AdminMarketplaceVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{vendor}/reject', [AdminMarketplaceVendorController::class, 'reject'])->name('vendors.reject');
    Route::post('/vendors/{vendor}/suspend', [AdminMarketplaceVendorController::class, 'suspend'])->name('vendors.suspend');
    Route::post('/vendors/{vendor}/reactivate', [AdminMarketplaceVendorController::class, 'reactivate'])->name('vendors.reactivate');
    Route::put('/vendors/{vendor}/commission', [AdminMarketplaceVendorController::class, 'commission'])->name('vendors.commission');

    // Catalogue dynamique : catégories + champs personnalisés
    Route::get('/categories', [AdminMarketplaceCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [AdminMarketplaceCategoryController::class, 'store'])->name('categories.store');
    Route::post('/categories/reorder', [AdminMarketplaceCategoryController::class, 'reorder'])->name('categories.reorder');
    Route::put('/categories/{category}', [AdminMarketplaceCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminMarketplaceCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::post('/categories/{category}/fields', [AdminMarketplaceCategoryFieldController::class, 'store'])->name('categories.fields.store');
    Route::put('/categories/{category}/fields/{field}', [AdminMarketplaceCategoryFieldController::class, 'update'])->name('categories.fields.update');
    Route::delete('/categories/{category}/fields/{field}', [AdminMarketplaceCategoryFieldController::class, 'destroy'])->name('categories.fields.destroy');

    // Modération des offres
    Route::get('/offers', [AdminMarketplaceOfferController::class, 'index'])->name('offers.index');
    Route::get('/offers/{offer}', [AdminMarketplaceOfferController::class, 'show'])->name('offers.show');
    Route::post('/offers/{offer}/approve', [AdminMarketplaceOfferController::class, 'approve'])->name('offers.approve');
    Route::post('/offers/{offer}/reject', [AdminMarketplaceOfferController::class, 'reject'])->name('offers.reject');
    Route::post('/offers/{offer}/request-changes', [AdminMarketplaceOfferController::class, 'requestChanges'])->name('offers.request-changes');
    Route::post('/offers/{offer}/suspend', [AdminMarketplaceOfferController::class, 'suspend'])->name('offers.suspend');
    Route::post('/offers/{offer}/unsuspend', [AdminMarketplaceOfferController::class, 'unsuspend'])->name('offers.unsuspend');
    Route::put('/offers/{offer}/commission', [AdminMarketplaceOfferController::class, 'commission'])->name('offers.commission');

    // Commandes
    Route::get('/orders', [App\Http\Controllers\Admin\Marketplace\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [App\Http\Controllers\Admin\Marketplace\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/mark-paid', [App\Http\Controllers\Admin\Marketplace\OrderController::class, 'markPaid'])->name('orders.mark-paid');
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\Admin\Marketplace\OrderController::class, 'cancel'])->name('orders.cancel');

    // Versements vendeurs
    Route::get('/payouts', [App\Http\Controllers\Admin\Marketplace\PayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{payout}/approve', [App\Http\Controllers\Admin\Marketplace\PayoutController::class, 'approve'])->name('payouts.approve');
    Route::post('/payouts/{payout}/mark-paid', [App\Http\Controllers\Admin\Marketplace\PayoutController::class, 'markPaid'])->name('payouts.mark-paid');
    Route::post('/payouts/{payout}/mark-failed', [App\Http\Controllers\Admin\Marketplace\PayoutController::class, 'markFailed'])->name('payouts.mark-failed');

    // Avis & remboursements
    Route::post('/reviews/{review}/moderate', [App\Http\Controllers\Admin\Marketplace\ReviewController::class, 'moderate'])->name('reviews.moderate');
    Route::post('/suborders/{suborder}/refund', [App\Http\Controllers\Admin\Marketplace\ReviewController::class, 'refund'])->name('suborders.refund');
});
