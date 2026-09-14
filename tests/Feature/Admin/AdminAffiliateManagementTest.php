<?php

namespace Tests\Feature\Admin;

use App\Exceptions\WithdrawalException;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AffiliationService;
use App\Services\WalletAdjustmentService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Affiliate\InteractsWithAffiliation;
use Tests\TestCase;

/**
 * §60-§62, §68 — panel admin : dashboard, gestion affilié, journal d'audit.
 */
class AdminAffiliateManagementTest extends TestCase
{
    use InteractsWithAffiliation;
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_cannot_access_the_admin_panel(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($client)->get('/admin/affiliates')->assertForbidden();
        $this->actingAs($client)->get('/admin/withdrawals')->assertForbidden();
    }

    public function test_admin_dashboard_shows_aggregate_stats(): void
    {
        $admin = $this->admin();
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine()]);
        app(AffiliationService::class)->distributeCommissions($order);

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Affiliés')
            ->assertSee('Chiffre d\'affaires')
            ->assertSee('1 500')   // commissions en attente
            ->assertSee('10 000'); // chiffre d'affaires (commande payée)
    }

    public function test_admin_can_search_and_view_an_affiliate_profile(): void
    {
        $admin = $this->admin();
        $jean = $this->affiliate(null, ['first_name' => 'Jean', 'phone' => '+22990000000', 'country' => 'BJ']);
        $pierre = $this->affiliate($jean, ['first_name' => 'Pierre']);

        // §61 — recherche (par identifiant affilié).
        $this->actingAs($admin)
            ->get('/admin/affiliates?q='.$pierre->affiliate_id)
            ->assertOk()
            ->assertSee($pierre->affiliate_id)
            ->assertSee('Ouvrir');

        // Une vente référée par Pierre + une commission pour Jean (niv. 2).
        $order = $this->paidOrder($pierre, [$this->digitalAffiliableLine()]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        // §62 — fiche : profil, parrain, lignée, ventes, commissions, retraits.
        $this->actingAs($admin)
            ->get("/admin/affiliates/{$pierre->id}")
            ->assertOk()
            ->assertSee($pierre->affiliate_id)
            ->assertSee('Profil')
            ->assertSee('Parrain')
            ->assertSee($jean->affiliate_id)          // son parrain
            ->assertSee('Lignée')
            ->assertSee('Niveau 1')->assertSee('Niveau 3')
            ->assertSee('Ventes')
            ->assertSee($order->order_number)          // sa vente
            ->assertSee('Commissions')
            ->assertSee('Retraits')
            ->assertSee('Ajustement du solde');
    }

    public function test_admin_can_suspend_and_reactivate_an_affiliate_with_audit_trail(): void
    {
        $admin = $this->admin();
        $jean = $this->affiliate();

        $this->actingAs($admin)
            ->from("/admin/affiliates/{$jean->id}")
            ->post("/admin/affiliates/{$jean->id}/suspend", ['category' => 'suspicious_activity', 'reason' => 'Activité suspecte'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('suspended', $jean->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $admin->id, 'action' => 'affiliate.suspended', 'subject_id' => $jean->id,
        ]);

        $this->actingAs($admin)
            ->from("/admin/affiliates/{$jean->id}")
            ->post("/admin/affiliates/{$jean->id}/reactivate")
            ->assertRedirect();

        $this->assertSame('active', $jean->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $admin->id, 'action' => 'affiliate.reactivated', 'subject_id' => $jean->id,
        ]);

        $log = AuditLog::where('action', 'affiliate.suspended')->first();
        $this->assertNotNull($log->description);
        $this->assertSame($admin->id, $log->admin_id);
    }

    public function test_suspension_defines_the_treatment_of_pending_available_and_processing_withdrawals(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $admin = $this->admin();
        $jean = $this->affiliate();

        // Une commission "en attente" (délai > 0) et une "disponible" (délai 0).
        $pendingOrder = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        config(['malkuthar.affiliation.validation_delay_days' => 30]);
        app(AffiliationService::class)->distributeCommissions($pendingOrder->fresh());

        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $availableOrder = $this->paidOrder($jean, [$this->digitalAffiliableLine(20000)]);
        app(AffiliationService::class)->distributeCommissions($availableOrder->fresh());
        app(AffiliationService::class)->releaseDueCommissions();

        // Un retrait en cours.
        $wallet = $jean->wallet;
        $wallet->update(['balance_available' => 30000]);
        $withdrawal = app(WithdrawalService::class)->requestWithdrawal($jean, [
            'amount' => 5000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);
        $this->assertSame('processing', $withdrawal->fresh()->status);

        $this->actingAs($admin)->post("/admin/affiliates/{$jean->id}/suspend", [
            'category' => 'fraud',
            'reason' => 'Auto-parrainage + comptes multiples',
            'treatment_pending' => 'cancel',
            'treatment_available' => 'clawback',
            'treatment_withdrawals' => 'rollback',
        ])->assertRedirect()->assertSessionHas('success');

        // §63 — commissions EN ATTENTE : annulées (statut, jamais supprimées).
        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $pendingOrder->id, 'source_type' => 'commission', 'status' => 'cancelled',
        ]);
        // Commissions DISPONIBLES : reprises via un débit d'ajustement tracé.
        $this->assertDatabaseHas('ledger_entries', [
            'user_id' => $jean->id, 'source_type' => 'adjustment', 'direction' => 'debit', 'admin_id' => $admin->id,
        ]);
        // Retrait EN COURS : annulé et fonds recrédités.
        $this->assertContains($withdrawal->fresh()->status, ['failed', 'cancelled']);

        // Rien n'est physiquement supprimé.
        $this->assertDatabaseHas('ledger_entries', ['order_id' => $availableOrder->id, 'source_type' => 'commission']);

        // Le journal d'audit contient le traitement retenu.
        $log = AuditLog::where('action', 'affiliate.suspended')->latest()->first();
        $this->assertSame('fraud', $log->changes['category']);
        $this->assertSame('cancel', $log->changes['treatment']['pending']);
    }

    public function test_suspended_affiliate_pending_commissions_stay_frozen_on_release(): void
    {
        config(['malkuthar.affiliation.validation_delay_days' => 0]);
        $jean = $this->affiliate();
        $order = $this->paidOrder($jean, [$this->digitalAffiliableLine(10000)]);
        app(AffiliationService::class)->distributeCommissions($order->fresh());

        // Suspension avec gel (défaut).
        $this->actingAs($this->admin())->post("/admin/affiliates/{$jean->id}/suspend", [
            'category' => 'suspicious_activity', 'reason' => 'Vérification en cours',
        ])->assertSessionHas('success');

        // La commande d'échéance ne libère PAS les commissions d'un suspendu.
        app(AffiliationService::class)->releaseDueCommissions();
        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id, 'source_type' => 'commission', 'status' => 'pending',
        ]);

        // Après réactivation, elles redeviennent libérables.
        $this->actingAs($this->admin())->post("/admin/affiliates/{$jean->id}/reactivate");
        app(AffiliationService::class)->releaseDueCommissions();
        $this->assertDatabaseHas('ledger_entries', [
            'order_id' => $order->id, 'source_type' => 'commission', 'status' => 'available',
        ]);
    }

    public function test_suspended_affiliate_cannot_request_a_withdrawal(): void
    {
        $jean = $this->affiliate(null, ['status' => 'suspended']);
        Wallet::create(['user_id' => $jean->id, 'currency' => 'XOF', 'balance_available' => 50000]);

        $this->expectException(WithdrawalException::class);
        app(WithdrawalService::class)->requestWithdrawal($jean, [
            'amount' => 10000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);
    }

    public function test_wallet_adjustment_is_traced_and_updates_balance(): void
    {
        $admin = $this->admin();
        $jean = $this->affiliate();
        Wallet::create(['user_id' => $jean->id, 'currency' => 'XOF', 'balance_available' => 10000]);

        $this->actingAs($admin)
            ->from("/admin/affiliates/{$jean->id}")
            ->post("/admin/affiliates/{$jean->id}/wallet-adjustments", [
                'amount' => 5000, 'direction' => 'credit', 'reason' => 'Geste commercial',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEqualsWithDelta(15000, (float) $jean->wallet->fresh()->balance_available, 0.001);
        $this->assertDatabaseHas('ledger_entries', [
            'user_id' => $jean->id, 'source_type' => 'adjustment', 'admin_id' => $admin->id, 'amount' => 5000,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $admin->id, 'action' => 'wallet.adjusted', 'subject_id' => $jean->id,
        ]);
    }

    public function test_wallet_adjustment_service_never_writes_balance_without_a_ledger_entry(): void
    {
        $admin = $this->admin();
        $jean = $this->affiliate();

        $entry = app(WalletAdjustmentService::class)->adjust($admin, $jean, 3000, 'debit', 'Correction erreur commission');

        $this->assertSame('adjustment', $entry->source_type);
        $this->assertSame($admin->id, $entry->admin_id);
        $this->assertSame('Correction erreur commission', $entry->adjustment_reason);
        $this->assertEqualsWithDelta(-3000, (float) $jean->wallet->fresh()->balance_available, 0.001);
    }

    public function test_audit_log_page_is_filterable_by_action(): void
    {
        $admin = $this->admin();
        $jean = $this->affiliate();

        $this->actingAs($admin)->post("/admin/affiliates/{$jean->id}/suspend", ['category' => 'fraud', 'reason' => 'x']);
        $this->actingAs($admin)->post("/admin/affiliates/{$jean->id}/reactivate");

        $this->actingAs($admin)
            ->get('/admin/audit-logs?action=affiliate.suspended')
            ->assertOk()
            ->assertSee('Suspension de')
            ->assertDontSee('Réactivation de');
    }

    public function test_admin_can_mark_a_processing_withdrawal_as_paid(): void
    {
        $admin = $this->admin();
        $jean = $this->affiliate();
        Wallet::create(['user_id' => $jean->id, 'currency' => 'XOF', 'balance_available' => 50000]);
        $withdrawal = app(WithdrawalService::class)->requestWithdrawal($jean, [
            'amount' => 10000, 'method' => 'mobile_money', 'account_details' => ['mobile_money_number' => '+22990000000'],
        ]);

        $this->actingAs($admin)->get('/admin/withdrawals')->assertOk()->assertSee($withdrawal->reference);

        $this->actingAs($admin)
            ->post("/admin/withdrawals/{$withdrawal->id}/mark-paid", ['provider_reference' => 'PAY-1'])
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('paid', $withdrawal->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'withdrawal.marked_paid']);
    }
}
