<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API interne MALKUTHAR (§65)
|--------------------------------------------------------------------------
|
| Ces endpoints seront alimentés au fil des phases. Conceptuellement :
|   POST /api/affiliate/register
|   GET  /api/affiliate/dashboard
|   GET  /api/affiliate/commissions
|   GET  /api/affiliate/sales
|   GET  /api/affiliate/team
|   GET  /api/affiliate/referral-link
|   POST /api/withdrawals
|   GET  /api/withdrawals
|   GET  /api/wallet
|
| L'authentification API (Sanctum) sera branchée à l'étape "Auth" de la
| chaîne d'affiliation.
|
*/

Route::get('/ping', fn () => response()->json(['status' => 'ok', 'service' => 'malkuthar-api']));
