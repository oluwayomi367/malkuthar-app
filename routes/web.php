<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\AffiliateDashboardController;


Route::post('/checkout', [CheckoutController::class, 'store']);
Route::post('/webhooks/fedapay', [PaymentWebhookController::class, 'handleFedaPayWebhook'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
Route::post('/affiliate/withdrawals', [WithdrawalController::class, 'requestWithdrawal'])->middleware('auth');
Route::get('/affiliate/dashboard', [AffiliateDashboardController::class, 'index'])
    ->middleware('auth')
    ->name('affiliate.dashboard');
Route::get('/', function () {
    return view('welcome');
});
