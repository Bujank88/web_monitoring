<?php

namespace Tests\Feature;

use App\Http\Controllers\OneSynergyReportController;
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
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        config(['database.connections.kam_myads' => config('database.connections.sqlite')]);
        DB::purge('sqlite');
        DB::purge('kam_myads');
        foreach (['2026_09_02_000000_create_one_synergy_reports_table',
            '2026_09_16_100000_create_one_synergy_monthly_multipliers_table',
            '2026_09_16_110000_split_one_synergy_monthly_multipliers',
            '2026_09_16_120000_add_channel_multipliers_to_one_synergy'] as $migration) {
            (require database_path('migrations/' . $migration . '.php'))->up();
        }
        Schema::connection('kam_myads')->create('users', function (Blueprint $table) {
            $table->string('merchant_id'); $table->string('name'); $table->string('role');
        });
        Schema::connection('kam_myads')->create('merchant_campaign_mappings', function (Blueprint $table) {
            $table->string('merchant_id'); $table->string('campaign_id');
        });
        DB::connection('kam_myads')->table('users')->insert([
            ['merchant_id' => 'CH778899', 'name' => 'ICE', 'role' => 'user'],
            ['merchant_id' => '808982JJ', 'name' => 'ICE', 'role' => 'user'],
        ]);
        $this->actingAs(new User(['id' => 1, 'role' => 'Admin']));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function campaign(string $id, string $merchant, string $date, string $category, string $channel, int $success): void
    {
        DB::table('one_synergy_reports')->insert([
            'id_iklan' => $id, 'tgl_tayang' => $date, 'kategori_iklan' => $category,
            'tipe_kanal' => $channel, 'sukses' => $success, 'total_harga' => 999999,
        ]);
        DB::connection('kam_myads')->table('merchant_campaign_mappings')->insert([
            'merchant_id' => $merchant, 'campaign_id' => $id,
        ]);
    }

    private function rows(string $month = '2026-09'): array
    {
        return $this->getJson(route('one-synergy.merchant-summary.data', ['month' => $month]))
            ->assertOk()->json('data');
    }

    public function test_summary_uses_all_six_rates_and_excludes_old_ice_and_duplicate_mappings(): void
    {
        $rates = ['month' => '2026-09'];
        $id = 0;
        foreach (['SMS', 'WABA'] as $category) {
            foreach (['LBA', 'BROADCAST', 'TARGETED'] as $channel) {
                $id++;
                $rates[strtolower($category . '_' . $channel) . '_multiplier'] = $id * 100.25;
                $this->campaign((string) $id, 'CH778899', '2026-09-15', $category, $channel, 4);
            }
        }
        DB::table('one_synergy_monthly_multipliers')->insert($rates);
        $this->campaign('old', '808982JJ', '2026-09-15', 'WABA', 'BROADCAST', 5304);
        DB::connection('kam_myads')->table('merchant_campaign_mappings')->insert(['merchant_id' => 'CH778899', 'campaign_id' => '1']);
        $rows = $this->rows();
        $total = end($rows);
        $this->assertSame('0', $rows[0]['total_balance']);
        $this->assertSame('8.421', $rows[14]['total_balance']);
        $this->assertSame(6, $total['total_campaign']);
        $this->assertSame('8.421', $total['merchant_' . md5('CH778899') . '_balance']);
        $this->assertArrayNotHasKey('merchant_' . md5('808982JJ') . '_balance', $total);
    }

    public function test_month_prices_and_reversed_columns_are_respected(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            ['month' => '2026-08', 'waba_broadcast_multiplier' => 100.25],
            ['month' => '2026-09', 'waba_broadcast_multiplier' => 586],
        ]);
        $this->campaign('1', 'CH778899', '2026-08-15', 'WABA', 'BROADCAST', 4);
        $this->campaign('2', 'CH778899', '2026-09-15', ' broadcast ', ' waba ', 4);
        $august = $this->rows('2026-08');
        $september = $this->rows();
        $this->assertSame('401', end($august)['total_balance']);
        $this->assertSame('2.344', end($september)['total_balance']);
    }

    public function test_missing_prices_are_not_reported_as_zero_or_partial_totals(): void
    {
        $this->campaign('1', 'CH778899', '2026-09-15', 'SMS', 'LBA', 10);
        $this->campaign('2', 'CH778899', '2026-09-15', 'WABA', 'BROADCAST', 10);
        DB::table('one_synergy_monthly_multipliers')->insert(['month' => '2026-09', 'waba_broadcast_multiplier' => 586]);
        $rows = $this->rows();
        $this->assertNull($rows[14]['total_balance']);
        $this->assertNull(end($rows)['total_balance']);
        DB::table('one_synergy_monthly_multipliers')->update(['sms_lba_multiplier' => 0]);
        $rows = $this->rows();
        $this->assertSame('5.860', end($rows)['total_balance']);
    }

    public function test_report_includes_all_merchants_and_unmapped_campaigns_for_both_roles(): void
    {
        $this->campaign('1', 'CH778899', '2026-09-15', 'WABA', 'BROADCAST', 10);
        $this->campaign('2', '808982JJ', '2026-09-15', 'WABA', 'BROADCAST', 5304);
        DB::table('one_synergy_reports')->insert([
            ['id_iklan' => '3', 'tgl_tayang' => '2026-09-16', 'sukses' => 0],
            ['id_iklan' => '4', 'tgl_tayang' => '2026-09-17', 'sukses' => 0],
            ['id_iklan' => '5', 'tgl_tayang' => '2026-10-01', 'sukses' => 0],
        ]);
        $controller = new OneSynergyReportController();
        $options = (new \ReflectionMethod($controller, 'merchantOptions'))->invoke($controller, true);
        $this->assertSame(['CH778899'], array_column($options, 'id'));
        $this->assertSame('ICE (CH778899)', $options[0]['label']);
        $reportOptions = (new \ReflectionMethod($controller, 'merchantOptions'))->invoke($controller, false, false);
        $this->assertEqualsCanonicalizing(['CH778899', '808982JJ'], array_column($reportOptions, 'id'));
        foreach (['Admin', '1Synergy'] as $role) {
            $this->actingAs(new User(['id' => 1, 'role' => $role]));
            $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))->assertOk()
                ->assertJsonPath('recordsTotal', 4)->assertJsonPath('summary.total_campaign', 4);
            $this->getJson(route('one-synergy.report.data', ['month' => '2026-09', 'merchant' => '808982JJ']))
                ->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.id_iklan', '2');
            $this->get(route('one-synergy.report.export', ['month' => '2026-09', 'merchant' => '808982JJ']))
                ->assertOk()->assertDownload('Report_1Synergy_2026-09.xlsx');
            $this->get(route('one-synergy.report.export', ['month' => '2026-09']))
                ->assertOk()->assertDownload('Report_1Synergy_2026-09.xlsx');
        }
    }
}
