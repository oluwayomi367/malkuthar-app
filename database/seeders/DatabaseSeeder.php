<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ServiceAvailabilityService;
use Illuminate\Database\Seeder;

/**
 * Jeu de données de démonstration pour tester dans le navigateur.
 * Tous les comptes ci-dessous ont le mot de passe : password
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Comptes ----
        app(\App\Services\AffiliateOnboardingService::class)->systemUser();

        User::factory()->admin()->create([
            'first_name' => 'Admin', 'last_name' => 'MALKUTHAR', 'email' => 'admin@malkuthar.test',
        ]);

        User::factory()->create([
            'first_name' => 'Client', 'last_name' => 'Test', 'email' => 'client@malkuthar.test',
        ]);

        $affiliate = User::factory()->affiliate()->create([
            'first_name' => 'Jean', 'last_name' => 'Affilie',
            'email' => 'affilie@malkuthar.test', 'code_parrainage' => 'JEAN125',
        ]);
        Wallet::firstOrCreate(['user_id' => $affiliate->id], ['currency' => $affiliate->currency]);

        // ---- Produits ----
        Product::factory()->digitalAffiliable()->create([
            'name' => 'Ebook — Réussir son business en ligne', 'slug' => 'ebook-business-en-ligne',
            'description' => "120 pages pour lancer votre activité digitale.\nLivraison immédiate après paiement.",
            'price' => 10000, 'category' => 'Ebooks',
        ]);

        Product::factory()->digitalAffiliable()->create([
            'name' => 'Formation vidéo — Marketing d\'affiliation', 'slug' => 'formation-marketing-affiliation',
            'description' => '6 modules vidéo + ressources téléchargeables.',
            'price' => 25000, 'category' => 'Formations',
        ]);

        Product::factory()->digitalNonAffiliable()->create([
            'name' => 'Pack audio — Méditation guidée', 'slug' => 'pack-audio-meditation',
            'description' => 'Produit digital NON affiliable : aucune commission.',
            'price' => 8000, 'category' => 'Audios',
        ]);

        Product::factory()->physical()->create([
            'name' => 'Carnet MALKUTHAR (couverture rigide)', 'slug' => 'carnet-malkuthar',
            'description' => 'Carnet A5, 200 pages. Livré sous 3 à 7 jours.',
            'price' => 12000, 'stock_available' => 40, 'category' => 'Accessoires',
        ]);

        $service = Product::factory()->service()->create([
            'name' => 'Consultation stratégie — 1h', 'slug' => 'consultation-strategie',
            'description' => 'Rendez-vous individuel en visioconférence.',
            'price' => 30000, 'duration_minutes' => 60, 'category' => 'Consultations',
            'delivery_mode' => 'visioconférence', 'meeting_link' => 'https://meet.example.com/malkuthar',
        ]);

        // Calendrier du service : lundi -> vendredi, 9h-12h et 14h-17h.
        app(ServiceAvailabilityService::class)->setWeeklySchedule($service, collect(range(1, 5))
            ->flatMap(fn ($weekday) => [
                ['weekday' => $weekday, 'start_time' => '09:00', 'end_time' => '12:00', 'capacity' => 1],
                ['weekday' => $weekday, 'start_time' => '14:00', 'end_time' => '17:00', 'capacity' => 1],
            ])->all());

        // ---- Place de marché (catégories + vendeur de démo + activation) ----
        $this->call(MarketplaceSeeder::class);

        $this->command?->info('Comptes de démo (mot de passe: password) : admin@malkuthar.test / client@malkuthar.test / affilie@malkuthar.test / vendeur@malkuthar.test');
    }
}
