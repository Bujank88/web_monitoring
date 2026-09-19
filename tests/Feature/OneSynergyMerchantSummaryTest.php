<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OneSynergyMerchantSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-15'));
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.kam_myads' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ],
        ]);
        DB::purge('sqlite');
        DB::purge('kam_myads');

        (require database_path('migrations/2026_09_02_000000_create_one_synergy_reports_table.php'))->up();
        Schema::create('one_synergy_monthly_multipliers', function (Blueprint $table) {
            $table->string('month')->unique();
            foreach (['sms', 'waba'] as $channel) {
                $table->decimal($channel . '_multiplier', 15, 2)->nullable();
                foreach (['lba', 'broadcast', 'targeted'] as $type) {
                    $table->decimal($channel . '_' . $type . '_multiplier', 15, 2)->nullable();
                }
            }
        });
        Schema::connection('kam_myads')->create('users', function (Blueprint $table) {
            $table->string('merchant_id');
            $table->string('name');
        });
        Schema::connection('kam_myads')->create('merchant_campaign_mappings', function (Blueprint $table) {
            $table->string('merchant_id');
            $table->string('campaign_id');
        });
        DB::connection('kam_myads')->table('users')->insert([
            ['merchant_id' => 'CH778899', 'name' => 'ICE'],
            ['merchant_id' => 'SECOND', 'name' => 'Second Merchant'],
        ]);
        $this->actingAs(new User(['id' => 1, 'role' => 'Admin']));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function campaign(string $id, string $merchant, string $date, string $category, string $type, int $success): void
    {
        DB::table('one_synergy_reports')->insert([
            'id_iklan' => $id, 'tgl_tayang' => $date, 'kategori_iklan' => $category,
            'tipe_kanal' => $type, 'sukses' => $success, 'gagal' => 100, 'refunded' => 50,
            'total_harga' => 999999,
        ]);
        DB::connection('kam_myads')->table('merchant_campaign_mappings')->insert([
            'merchant_id' => $merchant, 'campaign_id' => $id,
        ]);
    }

    private function summary(string $month): array
    {
        return $this->getJson(route('one-synergy.merchant-summary.data', ['month' => $month]))
            ->assertOk()->json('data');
    }

    public function test_daily_and_merchant_totals_use_success_and_each_channel_category_price(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            'month' => '2026-09', 'sms_multiplier' => 1, 'waba_multiplier' => 2,
            'sms_lba_multiplier' => 168, 'sms_broadcast_multiplier' => 84,
            'sms_targeted_multiplier' => 151, 'waba_lba_multiplier' => 968,
            'waba_broadcast_multiplier' => 586, 'waba_targeted_multiplier' => 968,
        ]);
        $this->campaign('1', 'CH778899', '2026-09-15', 'WABA', 'BROADCAST', 10);
        $this->campaign('2', 'CH778899', '2026-09-15', 'SMS', 'LBA', 2);
        $this->campaign('3', 'CH778899', '2026-09-16', ' targeted ', ' sms ', 3);
        $this->campaign('4', 'CH778899', '2026-09-15', 'BROADCAST', 'SMS', 4);
        $this->campaign('5', 'CH778899', '2026-09-16', 'WABA', 'LBA', 5);
        $this->campaign('6', 'CH778899', '2026-09-16', 'TARGETED', 'WABA', 6);
        $this->campaign('7', 'CH778899', '2026-09-16', 'WABA', 'BROADCAST', 0);
        $this->campaign('8', 'CH778899', '2026-08-15', 'WABA', 'BROADCAST', 1000);
        $this->campaign('9', 'SECOND', '2026-09-15', 'WABA', 'BROADCAST', 1000);

        $rows = $this->summary('2026-09');
        $ice = 'merchant_' . md5('CH778899');
        $second = 'merchant_' . md5('SECOND');
        $this->assertSame('0', $rows[0][$ice . '_balance']);
        $this->assertSame(3, $rows[14][$ice . '_campaign']);
        $this->assertSame('6.532', $rows[14][$ice . '_balance']);
        $this->assertSame('6.532', $rows[14]['total_balance']);
        $this->assertSame('11.101', $rows[15][$ice . '_balance']);
        $total = end($rows);
        $this->assertSame(7, $total[$ice . '_campaign']);
        $this->assertSame('17.633', $total[$ice . '_balance']);
        $this->assertArrayNotHasKey($second . '_balance', $total);
        $this->assertSame(7, $total['total_campaign']);
        $this->assertSame('17.633', $total['total_balance']);
    }

    public function test_selected_month_uses_its_own_price_and_preserves_decimal_rates(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            ['month' => '2026-08', 'waba_broadcast_multiplier' => 100.25],
            ['month' => '2026-09', 'waba_broadcast_multiplier' => 586],
        ]);
        $this->campaign('1', 'CH778899', '2026-08-15', 'WABA', 'BROADCAST', 4);
        $this->campaign('2', 'CH778899', '2026-09-15', 'WABA', 'BROADCAST', 4);

        $august = $this->summary('2026-08');
        $september = $this->summary('2026-09');
        $this->assertSame('401', end($august)['total_balance']);
        $this->assertSame('2.344', end($september)['total_balance']);
    }

    public function test_general_channel_price_is_used_when_category_price_is_null_but_zero_is_respected(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            'month' => '2026-09', 'sms_multiplier' => 84, 'sms_targeted_multiplier' => 0,
        ]);
        $this->campaign('1', 'CH778899', '2026-09-15', 'SMS', 'BROADCAST', 10);
        $this->campaign('2', 'CH778899', '2026-09-16', 'SMS', 'TARGETED', 10);

        $rows = $this->summary('2026-09');
        $this->assertSame('840', $rows[14]['total_balance']);
        $this->assertSame('0', $rows[15]['total_balance']);
        $this->assertSame('840', end($rows)['total_balance']);
    }

    public function test_missing_month_price_does_not_use_another_month_or_imported_total(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            'month' => '2026-09', 'waba_broadcast_multiplier' => 586,
        ]);
        $this->campaign('1', 'CH778899', '2026-08-15', 'WABA', 'BROADCAST', 10);

        $rows = $this->summary('2026-08');
        $this->assertSame(1, end($rows)['total_campaign']);
        $this->assertSame('0', end($rows)['total_balance']);
    }

    public function test_report_counts_both_column_layouts_and_matches_merchant_balance(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            'month' => '2026-09', 'sms_broadcast_multiplier' => 84, 'waba_broadcast_multiplier' => 586,
        ]);
        $this->campaign('1', 'CH778899', '2026-09-15', 'WABA', 'BROADCAST', 10);
        $this->campaign('2', 'CH778899', '2026-09-15', ' broadcast ', ' sms ', 4);
        $this->campaign('3', 'SECOND', '2026-09-15', 'SMS', 'BROADCAST', 100);

        $response = $this->getJson(route('one-synergy.report.data', [
            'month' => '2026-09', 'merchant' => 'CH778899',
            'columns' => [['data' => 'total_harga', 'name' => 'cr.balance_terpakai', 'orderable' => 'true']],
            'order' => [['column' => 0, 'dir' => 'asc']],
        ]))->assertOk()->assertJsonMissingPath('error');
        $response->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('summary.total_success_sms', 4)
            ->assertJsonPath('summary.total_success_waba', 10)
            ->assertJsonPath('summary.total_failed_sms', 150)
            ->assertJsonPath('summary.total_failed_waba', 150)
            ->assertJsonPath('summary.total_harga', 6196)
            ->assertJsonPath('data.0.total_harga', 'Rp 336')
            ->assertJsonPath('data.1.total_harga', 'Rp 5.860');
        $rows = $this->summary('2026-09');
        $this->assertSame('6.196', end($rows)['merchant_' . md5('CH778899') . '_balance']);
    }

    public function test_report_keeps_fractional_prices_until_display_rounding(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            ['month' => '2026-08', 'waba_broadcast_multiplier' => 100.25],
            ['month' => '2026-09', 'waba_broadcast_multiplier' => 586],
        ]);
        $this->campaign('1', 'CH778899', '2026-08-15', 'WABA', 'BROADCAST', 3);

        $this->getJson(route('one-synergy.report.data', ['month' => '2026-08']))->assertOk()
            ->assertJsonMissingPath('error')
            ->assertJsonPath('summary.total_harga', 300.75)
            ->assertJsonPath('data.0.total_harga', 'Rp 301');
    }

    private function createBalanceTransfersTable(): void
    {
        Schema::create('transaksi_balance_transfer', function (Blueprint $table) {
            $table->dateTime('tanggal');
            $table->string('email_penerima');
            $table->string('status');
            $table->decimal('jumlah', 18, 2);
        });
    }

    public function test_whitelist_applies_without_filters_and_cannot_be_bypassed_by_merchant_parameter(): void
    {
        $this->campaign('ice-report', 'CH778899', '2026-09-15', 'WABA', 'BROADCAST', 10);
        $this->campaign('other-report', 'SECOND', '2026-09-15', 'WABA', 'BROADCAST', 1000);
        DB::table('one_synergy_reports')->insert([
            'id_iklan' => 'unmapped-report', 'tgl_tayang' => '2026-09-15', 'sukses' => 500,
        ]);

        foreach (['Admin', '1Synergy'] as $role) {
            $this->actingAs(new User(['id' => 1, 'role' => $role]));
            $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))->assertOk()
                ->assertJsonMissingPath('error')
                ->assertJsonPath('recordsTotal', 1)
                ->assertJsonPath('data.0.id_iklan', 'ice-report');
            $this->getJson(route('one-synergy.report.data', ['month' => '2026-09', 'merchant' => 'SECOND']))->assertOk()
                ->assertJsonMissingPath('error')->assertJsonPath('recordsTotal', 0);
            $this->get(route('one-synergy.report.export', ['month' => '2026-09', 'merchant' => 'SECOND']))
                ->assertRedirect()->assertSessionHas('error', 'Tidak ada data 1Synergy untuk di-export.');

            $controller = $this->app->make(\App\Http\Controllers\OneSynergyReportController::class);
            $merchants = (new \ReflectionMethod($controller, 'merchantOptions'))->invoke($controller);
            $this->assertSame(['CH778899'], array_column($merchants, 'id'));
            $this->assertSame('ICE (CH778899)', $merchants[0]['label']);
        }
    }

    private function transfer(string $date, float $amount, string $email = 'arief_azhar@ptkam.co.id', string $status = 'Paid'): void
    {
        DB::table('transaksi_balance_transfer')->insert([
            'tanggal' => $date, 'jumlah' => $amount, 'email_penerima' => $email, 'status' => $status,
        ]);
    }

    private function balanceHistory(string $month): array
    {
        return (new \ReflectionMethod(\App\Http\Controllers\OneSynergyReportController::class, 'monitoringSaldoHistory'))
            ->invoke($this->app->make(\App\Http\Controllers\OneSynergyReportController::class), $month);
    }

    public function test_monitoring_uses_received_transfers_and_report_spending(): void
    {
        $this->createBalanceTransfersTable();
        $this->transfer('2026-08-27 14:28:04', 1000000);
        $this->transfer('2026-09-15 15:06:25', 50000000);
        $this->transfer('2026-09-15 15:10:24', 50000000);
        $this->transfer('2026-09-15 15:13:44', 50000000, ' ARIEF_AZHAR@PTKAM.CO.ID ', ' paid ');
        $this->transfer('2026-09-15 16:00:00', 50000000, 'someone@example.test');
        $this->transfer('2026-09-15 17:00:00', 50000000, 'arief_azhar@ptkam.co.id', 'Pending');
        DB::table('one_synergy_monthly_multipliers')->insert([
            'month' => '2026-09', 'waba_broadcast_multiplier' => 586,
        ]);
        $this->campaign('1', 'CH778899', '2026-09-15', 'WABA', 'BROADCAST', 5304);

        $history = $this->balanceHistory('2026-09');
        $this->assertEquals(1000000, $history['opening_balance']);
        $this->assertEquals(150000000, $history['total_in']);
        $this->assertEquals(3108144, $history['total_out']);
        $this->assertEquals(147891856, $history['ending_balance']);
        $this->assertEquals(147891856, $history['remaining_balance']);
        $this->assertCount(4, $history['rows']);
        $this->assertSame('Balance Terpakai Report 1Synergy', $history['rows'][0]['source']);
        $this->assertEquals(-2108144, $history['rows'][0]['running_balance']);
        $this->assertSame('Balance Transfer', $history['rows'][1]['source']);
        $this->assertEquals(147891856, $history['rows'][3]['running_balance']);
    }

    public function test_monitoring_opening_and_all_time_balance_use_each_reports_month_price(): void
    {
        $this->createBalanceTransfersTable();
        $this->transfer('2026-08-01 10:00:00', 1000000);
        $this->transfer('2026-09-01 10:00:00', 2000000);
        $this->transfer('2026-10-01 10:00:00', 3000000);
        DB::table('one_synergy_monthly_multipliers')->insert([
            ['month' => '2026-08', 'waba_broadcast_multiplier' => 100.25],
            ['month' => '2026-09', 'waba_broadcast_multiplier' => 586],
            ['month' => '2026-10', 'waba_broadcast_multiplier' => 600],
        ]);
        $this->campaign('1', 'CH778899', '2026-08-15', 'WABA', 'BROADCAST', 4);
        $this->campaign('2', 'CH778899', '2026-09-15', 'BROADCAST', 'WABA', 2);
        $this->campaign('3', 'CH778899', '2026-09-15', 'WABA', 'BROADCAST', 3);
        $this->campaign('4', 'CH778899', '2026-10-15', 'WABA', 'BROADCAST', 5);
        $this->campaign('5', 'SECOND', '2026-08-15', 'WABA', 'BROADCAST', 1000);
        $this->campaign('6', 'SECOND', '2026-09-15', 'WABA', 'BROADCAST', 1000);
        $this->campaign('7', 'SECOND', '2026-10-15', 'WABA', 'BROADCAST', 1000);

        $history = $this->balanceHistory('2026-09');
        $this->assertEquals(999599, $history['opening_balance']);
        $this->assertEquals(2000000, $history['total_in']);
        $this->assertEquals(2930, $history['total_out']);
        $this->assertEquals(2996669, $history['ending_balance']);
        $this->assertEquals(5993669, $history['remaining_balance']);
        $this->assertCount(2, $history['rows']);
        $this->assertEquals(2996669, $history['rows'][1]['running_balance']);
        $this->assertEquals($history['remaining_balance'], $this->balanceHistory('2026-08')['remaining_balance']);
    }
}
