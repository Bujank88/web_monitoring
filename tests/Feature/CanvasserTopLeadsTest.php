<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CanvasserTopLeadsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-22 12:00:00'));
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'role', 'email', 'regional'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('leads_master', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('email');
            $table->string('company_name')->nullable();
        });
        Schema::create('report_balance_top_up', function (Blueprint $table) {
            $table->id();
            $table->string('email_client');
            $table->dateTime('tgl_transaksi');
            $table->dateTime('paid_date')->nullable();
            $table->decimal('total_settlement_klien', 15, 2);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payment_method_name');
        });
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.test', 'role' => 'cvsr'],
            ['id' => 2, 'name' => 'Other', 'email' => 'other@example.test', 'role' => 'cvsr'],
            ['id' => 3, 'name' => 'Admin', 'email' => 'admin@example.test', 'role' => 'Admin'],
            ['id' => 4, 'name' => 'MPCC', 'email' => 'mpcc@example.test', 'role' => 'MPCC'],
        ]);
        $this->actingAs(User::findOrFail(1));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function url(string $suffix = 'top-leads', array $params = []): string
    {
        return route('topup-canvasser.detail'.($suffix ? '.'.$suffix : ''), $params + ['user_id' => 1, 'month' => '2026-09']);
    }

    private function lead(string $email, string $name = 'Customer', int $userId = 1): void
    {
        DB::table('leads_master')->insert(['user_id' => $userId, 'email' => $email, 'company_name' => $name]);
    }

    private function topup(string $email, float $amount, string $date, array $extra = []): void
    {
        if (isset($extra['paid_date'])) {
            $extra['paid_date'] = Carbon::parse($extra['paid_date'])->toDateTimeString();
        }
        DB::table('report_balance_top_up')->insert($extra + [
            'email_client' => $email, 'total_settlement_klien' => $amount,
            'tgl_transaksi' => Carbon::parse($date)->toDateTimeString(), 'payment_method_name' => 'Transfer',
        ]);
    }

    public function test_ranking_is_limited_scoped_deduplicated_and_masked_with_equal_month_cutoffs(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $email = 'client'.$i.'@example.test';
            $this->lead($email, 'Customer '.$i);
            $this->topup($email, $i * 100, '2026-09-22 23:59:59');
            $this->topup($email, $i * 50, '2026-08-22 23:59:59');
            $this->topup($email, 50000, '2026-08-23 00:00:00');
            $this->topup($email, 50000, '2026-09-23 00:00:00');
        }
        $this->lead(' CLIENT12@example.test ', 'Customer 12');
        $this->topup(' CLIENT12@example.test ', 100, '2026-09-01 00:00:00');
        $this->topup('client1@example.test', 90000, '2026-09-10', ['payment_method_name' => 'Voucher Bonus']);
        $this->lead('other@example.test', 'Other customer', 2);
        $this->topup('other@example.test', 90000, '2026-09-10');
        $this->topup('unknown@example.test', 90000, '2026-09-10');

        $response = $this->getJson($this->url())->assertOk()->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.company_name', 'Customer 12')
            ->assertJsonPath('data.0.current_total', 1300)->assertJsonPath('data.0.previous_total', 600)
            ->assertJsonPath('data.0.difference', 700)->assertJsonPath('data.0.mom_percent', 116.67)
            ->assertJsonPath('data.9.company_name', 'Customer 3');
        $this->assertStringNotContainsString('@example.test', $response->getContent());
        $this->assertArrayNotHasKey('normalized_email', $response->json('data.0'));
        $this->assertArrayNotHasKey('email', $response->json('data.0'));
    }

    public function test_new_and_declining_leads_and_empty_results(): void
    {
        $this->getJson($this->url())->assertOk()->assertJsonCount(0, 'data');
        $this->lead('a@b.c', 'New');
        $this->lead('decline@example.test', 'Declining');
        $this->topup('a@b.c', 200, '2026-09-01');
        $this->topup('decline@example.test', 100, '2026-09-01');
        $this->topup('decline@example.test', 200, '2026-08-01');
        $this->getJson($this->url())->assertOk()->assertJsonPath('data.0.mom_percent', null)
            ->assertJsonPath('data.0.masked_email', 'a****')
            ->assertJsonPath('data.1.mom_percent', -50)->assertJsonPath('data.1.difference', -100);
    }

    public function test_completed_month_compares_entire_previous_month_and_handles_year_boundary(): void
    {
        $this->lead('client@example.test');
        $this->topup('client@example.test', 100, '2026-02-28 23:59:59');
        $this->topup('client@example.test', 200, '2026-01-31 23:59:59');
        $this->topup('client@example.test', 400, '2025-12-31 23:59:59');
        $this->getJson($this->url(params: ['month' => '2026-02']))->assertOk()
            ->assertJsonPath('data.0.current_total', 100)->assertJsonPath('data.0.previous_total', 200);
        $this->getJson($this->url(params: ['month' => '2026-01']))->assertOk()
            ->assertJsonPath('data.0.current_total', 200)->assertJsonPath('data.0.previous_total', 400);
        Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00'));
        $this->topup('client@example.test', 300, '2026-03-31 23:59:59');
        $this->getJson($this->url(params: ['month' => '2026-03']))->assertOk()
            ->assertJsonPath('data.0.current_total', 300)->assertJsonPath('data.0.previous_total', 100);
    }

    public function test_csv_and_button_are_only_available_for_owner_or_admin_even_when_url_is_requested_directly(): void
    {
        $this->lead('secret@example.test', '=HYPERLINK("example.test")');
        $this->topup('secret@example.test', 100, '2026-09-01');
        $this->get($this->url(''))->assertOk()->assertSee('Download CSV')->assertSee('Top 10 Leads Master');
        $csv = $this->get($this->url('top-leads.csv'))->assertOk()
            ->assertDownload('top-10-leads-1-2026-09.csv')->streamedContent();
        $this->assertStringContainsString('secret@example.test', $csv);
        $this->assertStringNotContainsString('Email (Disensor)', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString('Baru', $csv);
        $this->actingAs(User::findOrFail(2));
        $this->get($this->url(''))->assertOk()->assertDontSee('Download CSV');
        $this->getJson($this->url('top-leads.csv'))->assertForbidden();
        $this->getJson($this->url())->assertOk();

        $this->actingAs(User::findOrFail(3));
        foreach ([1 => 'canvasser', 2 => 'canvasser', 4 => 'mpcc'] as $id => $source) {
            $params = ['user_id' => $id, 'source' => $source];
            $this->get($this->url('', $params))->assertOk()->assertSee('Download CSV');
            $adminCsv = $this->get($this->url('top-leads.csv', $params))->assertOk()
                ->assertDownload("top-10-leads-{$id}-2026-09.csv")->streamedContent();
            if ($id === 1) {
                $this->assertStringContainsString('secret@example.test', $adminCsv);
                $this->assertSame($csv, $adminCsv);
            } else {
                $this->assertStringNotContainsString('secret@example.test', $adminCsv);
            }
        }
        foreach ([1, 2, 3] as $viewerId) {
            $this->actingAs(User::findOrFail($viewerId));
            $response = $this->getJson($this->url(params: ['includeEmail' => true]))->assertOk();
            $this->assertStringNotContainsString('secret@example.test', $response->getContent());
            $this->assertArrayNotHasKey('email', $response->json('data.0'));
        }
        auth()->logout();
        $this->getJson($this->url('top-leads.csv'))->assertUnauthorized();
        $this->getJson($this->url())->assertUnauthorized();
    }

    public function test_mpcc_uses_paid_date_and_amount_and_invalid_filters_are_rejected(): void
    {
        $this->actingAs(User::findOrFail(3));
        $this->lead('mpcc-client@example.test', 'MPCC Customer', 4);
        $this->topup('mpcc-client@example.test', 999, '2026-08-01', ['paid_date' => '2026-09-01', 'amount' => 300]);
        $this->topup('mpcc-client@example.test', 999, '2026-08-02', ['amount' => 100]);
        $this->getJson($this->url(params: ['user_id' => 4, 'source' => 'mpcc']))->assertOk()
            ->assertJsonPath('data.0.current_total', 300)->assertJsonPath('data.0.previous_total', 100);
        $this->getJson($this->url(params: ['month' => '2026-13']))->assertUnprocessable();
        $this->getJson($this->url(params: ['source' => 'invalid']))->assertUnprocessable();
        $this->getJson($this->url(params: ['user_id' => 999]))->assertUnprocessable();
        $this->getJson($this->url(params: ['user_id' => 4]))->assertNotFound();
    }
}
