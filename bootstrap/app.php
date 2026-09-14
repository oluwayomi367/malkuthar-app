<?php

use App\Http\Middleware\CaptureUtmParameters;
use App\Http\Middleware\EnsureMarketplaceEnabled;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsVendor;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // §42 — validation des commissions arrivées à échéance.
        $schedule->command('affiliate:release-commissions')
            ->hourly()
            ->withoutOverlapping();

        // §19 — rappels de rendez-vous à venir.
        $schedule->command('bookings:send-reminders')
            ->hourly()
            ->withoutOverlapping();

        // MARKETPLACE — libération des revenus vendeurs échus.
        $schedule->command('marketplace:release-earnings')
            ->hourly()
            ->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware) {
        // §26/§27 — les webhooks PSP (FedaPay, CinetPay) sont des appels
        // serveur-à-serveur sans jeton CSRF Laravel : on les exclut de la
        // vérification. L'authenticité est garantie par la signature HMAC
        // + la re-vérification via l'API du PSP dans le Service dédié.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        // §72/§75 — langue + capture UTM sur toutes les pages web.
        $middleware->web(append: [
            SetLocale::class,
            CaptureUtmParameters::class,
        ]);

        // §60-§68 — routes d'administration.
        // Marketplace : `marketplace` = drapeau maître ; `vendor` = espace
        // vendeur (profil actif requis).
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'marketplace' => EnsureMarketplaceEnabled::class,
            'vendor' => EnsureUserIsVendor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
