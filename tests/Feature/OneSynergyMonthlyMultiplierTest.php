<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class OneSynergyMonthlyMultiplierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_02_000000_create_one_synergy_reports_table.php'))->up();
        (require database_path('migrations/2026_09_16_100000_create_one_synergy_monthly_multipliers_table.php'))->up();
        (require database_path('migrations/2026_09_16_110000_split_one_synergy_monthly_multipliers.php'))->up();
        $this->loginAs('Admin');
        DB::table('one_synergy_reports')->insert([
            ['id_iklan' => '1', 'tgl_tayang' => '2026-09-01', 'tipe_kanal' => 'SMS', 'sukses' => 10, 'total_harga' => 999999],
            ['id_iklan' => '2', 'tgl_tayang' => '2026-09-30', 'tipe_kanal' => 'WABA', 'sukses' => 20, 'total_harga' => 888888],
            ['id_iklan' => '3', 'tgl_tayang' => '2026-10-01', 'tipe_kanal' => 'SMS', 'sukses' => 100, 'total_harga' => 777777],
        ]);
    }

    private function loginAs(string $role): void
    {
        $user = new User();
        $user->forceFill(['id' => 1, 'name' => 'Test', 'email' => 'test@example.com', 'role' => $role]);
        $this->actingAs($user);
    }

    public function test_admin_can_save_and_update_separate_months(): void
    {
        $url = route('one-synergy.monthly-multipliers.store');
        $this->post($url, ['month' => '2026-09', 'waba_multiplier' => 300, 'sms_multiplier' => '150.25'])->assertRedirect();
        $this->post($url, ['month' => '2026-10', 'waba_multiplier' => 300, 'sms_multiplier' => '200'])->assertRedirect();
        $this->post($url, ['month' => '2026-09', 'waba_multiplier' => 300, 'sms_multiplier' => '175.50'])->assertRedirect();
        $this->assertDatabaseCount('one_synergy_monthly_multipliers', 2);
        $this->assertDatabaseHas('one_synergy_monthly_multipliers', ['month' => '2026-09', 'waba_multiplier' => 300, 'sms_multiplier' => 175.5, 'updated_by' => 1]);
        $this->assertDatabaseHas('one_synergy_monthly_multipliers', ['month' => '2026-10', 'waba_multiplier' => 300, 'sms_multiplier' => 200]);
        $this->get(route('one-synergy.monthly-multipliers', ['month' => '2026-09']))
            ->assertOk()->assertSee('Pengali Bulanan 1Synergy')->assertSee('175,50');
    }

    public function test_non_admin_cannot_view_or_save_settings(): void
    {
        foreach (['1Synergy', 'PH'] as $role) {
            $this->loginAs($role);
            $this->get(route('one-synergy.monthly-multipliers'))->assertRedirect('/');
            $this->post(route('one-synergy.monthly-multipliers.store'), ['month' => '2026-09', 'waba_multiplier' => 300, 'sms_multiplier' => 1])->assertRedirect('/');
        }
        $this->assertDatabaseCount('one_synergy_monthly_multipliers', 0);
    }

    public function test_invalid_settings_are_rejected(): void
    {
        foreach ([-1, '1.234', null] as $invalidWaba) {
            $this->postJson(route('one-synergy.monthly-multipliers.store'), [
                'month' => '2026-09', 'sms_multiplier' => 150, 'waba_multiplier' => $invalidWaba,
            ])->assertUnprocessable()->assertJsonValidationErrors('waba_multiplier');
        }
        foreach ([['month' => '2026-13', 'waba_multiplier' => 300, 'sms_multiplier' => 1], ['month' => '2026-09', 'waba_multiplier' => 300, 'sms_multiplier' => -1], ['month' => '2026-09', 'waba_multiplier' => 300, 'sms_multiplier' => '1.234']] as $payload) {
            $this->postJson(route('one-synergy.monthly-multipliers.store'), $payload)->assertUnprocessable();
        }
        $this->assertDatabaseCount('one_synergy_monthly_multipliers', 0);
    }

    public function test_summary_uses_success_and_selected_month_rate_without_exposing_row_prices(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            ['month' => '2026-09', 'waba_multiplier' => 300, 'sms_multiplier' => 150.25],
            ['month' => '2026-10', 'waba_multiplier' => 300, 'sms_multiplier' => 200],
        ]);
        $this->loginAs('1Synergy');
        $response = $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', 7502.5)
            ->assertJsonPath('summary.total_success_sms', 10)->assertJsonPath('summary.total_success_waba', 20);
        foreach ($response->json('data') as $row) {
            $this->assertArrayNotHasKey('total_harga', $row);
        }
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-10']))
            ->assertOk()->assertJsonPath('summary.total_harga', 20000);
    }

    public function test_missing_rate_is_distinct_from_zero(): void
    {
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', null);
        DB::table('one_synergy_monthly_multipliers')->insert(['month' => '2026-09', 'waba_multiplier' => null, 'sms_multiplier' => 0]);
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', null);
        DB::table('one_synergy_monthly_multipliers')->update(['waba_multiplier' => 0]);
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', 0);
    }

    public function test_merchant_filter_applies_to_success_total(): void
    {
        config(['database.connections.kam_myads' => config('database.connections.sqlite')]);
        DB::purge('kam_myads');
        Schema::connection('kam_myads')->create('merchant_campaign_mappings', function (Blueprint $table) {
            $table->string('merchant_id');
            $table->string('campaign_id');
        });
        DB::connection('kam_myads')->table('merchant_campaign_mappings')->insert(['merchant_id' => 'merchant-a', 'campaign_id' => '2']);
        DB::table('one_synergy_monthly_multipliers')->insert(['month' => '2026-09', 'waba_multiplier' => 300, 'sms_multiplier' => 150]);
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09', 'merchant' => 'merchant-a']))
            ->assertOk()->assertJsonPath('summary.total_harga', 6000)->assertJsonPath('summary.total_campaign', 1);
    }

    public function test_excel_export_has_no_price_column(): void
    {
        $this->loginAs('1Synergy');
        $response = $this->get(route('one-synergy.report.export', ['month' => '2026-09']))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'synergy-export-');
        try {
            file_put_contents($path, $response->streamedContent());
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
            $headers = $sheet->rangeToArray('A3:N3')[0];
            $this->assertNotContains('Total Harga', $headers);
            $this->assertSame('Detil Status', $headers[13]);
            $this->assertSame('N', $sheet->getHighestColumn());
        } finally {
            unlink($path);
        }
    }

    public function test_migration_preserves_existing_monthly_rates(): void
    {
        Schema::drop('one_synergy_monthly_multipliers');
        (require database_path('migrations/2026_09_16_100000_create_one_synergy_monthly_multipliers_table.php'))->up();
        DB::table('one_synergy_monthly_multipliers')->insert(['month' => '2026-09', 'multiplier' => 125.5]);
        (require database_path('migrations/2026_09_16_110000_split_one_synergy_monthly_multipliers.php'))->up();
        $this->assertDatabaseHas('one_synergy_monthly_multipliers', [
            'month' => '2026-09', 'sms_multiplier' => 125.5, 'waba_multiplier' => 125.5,
        ]);
    }
}
