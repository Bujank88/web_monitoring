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
        (require database_path('migrations/2026_09_16_120000_add_channel_multipliers_to_one_synergy.php'))->up();
        $this->loginAs('Admin');
        DB::table('one_synergy_reports')->insert([
            ['id_iklan' => '1', 'tgl_tayang' => '2026-09-01', 'kategori_iklan' => 'SMS', 'tipe_kanal' => 'LBA', 'sukses' => 10, 'total_harga' => 999999],
            ['id_iklan' => '2', 'tgl_tayang' => '2026-09-30', 'kategori_iklan' => 'WABA', 'tipe_kanal' => 'BROADCAST', 'sukses' => 20, 'total_harga' => 888888],
            ['id_iklan' => '3', 'tgl_tayang' => '2026-10-01', 'kategori_iklan' => 'SMS', 'tipe_kanal' => 'LBA', 'sukses' => 100, 'total_harga' => 777777],
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
        $otherRates = ['sms_broadcast_multiplier' => 12, 'sms_targeted_multiplier' => 23, 'waba_lba_multiplier' => 34, 'waba_targeted_multiplier' => 45];
        $this->post($url, $otherRates + ['month' => '2026-09', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => '150.25'])->assertSessionHasNoErrors()->assertRedirect();
        $this->post($url, $otherRates + ['month' => '2026-10', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => '200'])->assertSessionHasNoErrors()->assertRedirect();
        $this->post($url, $otherRates + ['month' => '2026-09', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => '175.50'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('one_synergy_monthly_multipliers', $otherRates + ['month' => '2026-09']);
        $this->assertDatabaseCount('one_synergy_monthly_multipliers', 2);
        $this->assertDatabaseHas('one_synergy_monthly_multipliers', ['month' => '2026-09', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => 175.5, 'updated_by' => 1]);
        $this->assertDatabaseHas('one_synergy_monthly_multipliers', ['month' => '2026-10', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => 200]);
        $this->get(route('one-synergy.monthly-multipliers', ['month' => '2026-09']))
            ->assertOk()->assertSee('Pengali Bulanan 1Synergy')->assertSee('175,50');
    }

    public function test_non_admin_cannot_view_or_save_settings(): void
    {
        foreach (['1Synergy', 'PH'] as $role) {
            $this->loginAs($role);
            $this->get(route('one-synergy.monthly-multipliers'))->assertRedirect('/');
            $this->post(route('one-synergy.monthly-multipliers.store'), ['month' => '2026-09', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => 1])->assertRedirect('/');
        }
        $this->assertDatabaseCount('one_synergy_monthly_multipliers', 0);
    }

    public function test_invalid_settings_are_rejected(): void
    {
        foreach ([-1, '1.234', null] as $invalidWaba) {
            $this->postJson(route('one-synergy.monthly-multipliers.store'), [
                'month' => '2026-09', 'sms_lba_multiplier' => 150, 'waba_broadcast_multiplier' => $invalidWaba,
            ])->assertUnprocessable()->assertJsonValidationErrors('waba_broadcast_multiplier');
        }
        foreach ([['month' => '2026-13', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => 1], ['month' => '2026-09', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => -1], ['month' => '2026-09', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => '1.234']] as $payload) {
            $this->postJson(route('one-synergy.monthly-multipliers.store'), $payload)->assertUnprocessable();
        }
        $this->assertDatabaseCount('one_synergy_monthly_multipliers', 0);
    }

    public function test_summary_uses_success_and_selected_month_rate_without_exposing_row_prices(): void
    {
        DB::table('one_synergy_monthly_multipliers')->insert([
            ['month' => '2026-09', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => 150.25],
            ['month' => '2026-10', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => 200],
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
        DB::table('one_synergy_monthly_multipliers')->insert(['month' => '2026-09', 'waba_broadcast_multiplier' => null, 'sms_lba_multiplier' => 0]);
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', null);
        DB::table('one_synergy_monthly_multipliers')->update(['waba_broadcast_multiplier' => 0]);
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', 0);
    }

    public function test_each_category_and_channel_uses_its_own_monthly_rate(): void
    {
        DB::table('one_synergy_reports')->delete();
        $rates = [
            'month' => '2026-09',
            'sms_lba_multiplier' => 1.25, 'sms_broadcast_multiplier' => 2, 'sms_targeted_multiplier' => 3,
            'waba_lba_multiplier' => 4, 'waba_broadcast_multiplier' => 5, 'waba_targeted_multiplier' => 6,
        ];
        $this->post(route('one-synergy.monthly-multipliers.store'), $rates)->assertSessionHasNoErrors()->assertRedirect();
        $index = 0;
        foreach (['SMS', 'WABA'] as $category) {
            foreach (['LBA', ' broadcast ', 'Targeted'] as $channel) {
                $index++;
                DB::table('one_synergy_reports')->insert([
                    'id_iklan' => (string) $index, 'tgl_tayang' => '2026-09-15',
                    'kategori_iklan' => $category, 'tipe_kanal' => $channel, 'sukses' => $index * 10,
                ]);
            }
        }
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', 912.5)
            ->assertJsonPath('summary.total_success_sms', 60)->assertJsonPath('summary.total_success_waba', 150);

        $rates['waba_targeted_multiplier'] = 10;
        $this->post(route('one-synergy.monthly-multipliers.store'), $rates)->assertSessionHasNoErrors()->assertRedirect();
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', 1152.5);

        DB::table('one_synergy_reports')->where('id_iklan', '6')->update(['tipe_kanal' => 'UNKNOWN']);
        $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_harga', null);
    }

    public function test_admin_must_fill_all_six_channel_rates(): void
    {
        $rates = ['month' => '2026-09', 'sms_lba_multiplier' => 1, 'sms_broadcast_multiplier' => 2,
            'sms_targeted_multiplier' => 3, 'waba_lba_multiplier' => 4,
            'waba_broadcast_multiplier' => 5, 'waba_targeted_multiplier' => 6];
        foreach (array_keys($rates) as $field) {
            if ($field === 'month') continue;
            $incomplete = $rates;
            unset($incomplete[$field]);
            $this->postJson(route('one-synergy.monthly-multipliers.store'), $incomplete)
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('one_synergy_monthly_multipliers', 0);
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
        DB::table('one_synergy_monthly_multipliers')->insert(['month' => '2026-09', 'waba_broadcast_multiplier' => 300, 'sms_lba_multiplier' => 150]);
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
            $headers = $sheet->rangeToArray('A3:M3')[0];
            $this->assertNotContains('Total Harga', $headers);
            $this->assertNotContains('Refunded', $headers);
            $this->assertSame('Detil Status', $headers[12]);
            $this->assertSame('M', $sheet->getHighestColumn());
        } finally {
            unlink($path);
        }
    }

    public function test_csv_without_refund_imports_correct_columns_and_channel_totals(): void
    {
        \Illuminate\Support\Facades\Storage::fake(config('filesystems.default'));
        $file = new \Illuminate\Http\UploadedFile(public_path('examples/report-1synergy.csv'), 'example.csv', 'text/csv', null, true);
        $this->post(route('one-synergy.upload.store'), ['report_file' => $file])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('one_synergy_reports', [
            'id_iklan' => '1849001', 'sukses' => 5304, 'gagal' => 1131, 'read' => 2929, 'click' => 0, 'refunded' => 0,
        ]);
        // Legacy refund values must not affect Failed in either rows or summary.
        DB::table('one_synergy_reports')->update(['refunded' => 9999]);
        DB::table('one_synergy_monthly_multipliers')->insert(['month' => '2026-09', 'sms_lba_multiplier' => 100, 'waba_broadcast_multiplier' => 200]);
        $response = $this->getJson(route('one-synergy.report.data', ['month' => '2026-09']))
            ->assertOk()->assertJsonPath('summary.total_failed_waba', 1131)
            ->assertJsonPath('summary.total_failed_sms', 7)
            ->assertJsonPath('summary.total_success_waba', 5304)
            ->assertJsonPath('summary.total_harga', 1070800);
        $row = collect($response->json('data'))->firstWhere('id_iklan', '1849001');
        $this->assertEquals(1131, $row['failed']);
        $this->assertEquals(2929, $row['read']);
    }

    public function test_reordered_csv_and_legacy_refund_do_not_shift_read(): void
    {
        \Illuminate\Support\Facades\Storage::fake(config('filesystems.default'));
        $csv = "CLICK,READ,REFUNDED,DETIL STATUS,TIPE KANAL,KATEGORI IKLAN,OPERATOR SELULER,JUDUL PESAN IKLAN,TGL TAYANG,ID IKLAN\n"
            . "29,75,3000,Sukses: 105 Gagal: 9,BROADCAST,WABA,TELKOMSEL,Example,15 Sep 2026,1849001\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('legacy.csv', $csv);
        $this->post(route('one-synergy.upload.store'), ['report_file' => $file])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('one_synergy_reports', ['id_iklan' => '1849001', 'gagal' => 9, 'refunded' => 0, 'read' => 75, 'click' => 29]);
    }

    public function test_missing_csv_header_does_not_replace_existing_data(): void
    {
        \Illuminate\Support\Facades\Storage::fake(config('filesystems.default'));
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('invalid.csv', "ID IKLAN,READ\n1,10\n");
        $this->post(route('one-synergy.upload.store'), ['report_file' => $file])->assertSessionHasErrors('report_file');
        $this->assertDatabaseCount('one_synergy_reports', 3);
    }

    public function test_merchant_summary_only_includes_user_role(): void
    {
        config(['database.connections.kam_myads' => config('database.connections.sqlite')]);
        DB::purge('kam_myads');
        Schema::connection('kam_myads')->create('merchant_campaign_mappings', function (Blueprint $table) {
            $table->string('merchant_id');
            $table->string('campaign_id');
        });
        Schema::connection('kam_myads')->create('users', function (Blueprint $table) {
            $table->string('merchant_id');
            $table->string('name');
            $table->string('role');
        });
        DB::connection('kam_myads')->table('users')->insert([
            ['merchant_id' => 'customer', 'name' => 'Customer', 'role' => 'user'],
            ['merchant_id' => 'customer', 'name' => 'Z Admin', 'role' => 'admin'],
            ['merchant_id' => 'staff', 'name' => 'Staff', 'role' => 'admin'],
        ]);
        DB::connection('kam_myads')->table('merchant_campaign_mappings')->insert([
            ['merchant_id' => 'customer', 'campaign_id' => '1'],
            ['merchant_id' => 'staff', 'campaign_id' => '2'],
            ['merchant_id' => 'orphan', 'campaign_id' => '2'],
        ]);
        $controller = new \App\Http\Controllers\OneSynergyReportController();
        $optionsMethod = new \ReflectionMethod($controller, 'merchantOptions');
        $options = $optionsMethod->invoke($controller, true);
        $this->assertCount(1, $options);
        $this->assertSame('customer', $options[0]['id']);
        $this->assertSame('Customer (customer)', $options[0]['label']);
        $this->assertCount(3, $optionsMethod->invoke($controller));
        $rows = (new \ReflectionMethod($controller, 'merchantSummaryRows'))->invoke($controller, '2026-09');
        $total = end($rows);
        $this->assertSame(1, $total['total_campaign']);
        $this->assertSame('999.999', $total['total_balance']);
        $this->assertArrayNotHasKey('merchant_' . md5('staff') . '_campaign', $total);
        $this->assertArrayNotHasKey('merchant_' . md5('orphan') . '_campaign', $total);
    }

    public function test_migration_preserves_existing_monthly_rates(): void
    {
        Schema::drop('one_synergy_monthly_multipliers');
        (require database_path('migrations/2026_09_16_100000_create_one_synergy_monthly_multipliers_table.php'))->up();
        DB::table('one_synergy_monthly_multipliers')->insert(['month' => '2026-09', 'multiplier' => 125.5]);
        (require database_path('migrations/2026_09_16_110000_split_one_synergy_monthly_multipliers.php'))->up();
        DB::table('one_synergy_monthly_multipliers')->update(['waba_multiplier' => 250]);
        (require database_path('migrations/2026_09_16_120000_add_channel_multipliers_to_one_synergy.php'))->up();
        $this->assertDatabaseHas('one_synergy_monthly_multipliers', [
            'month' => '2026-09', 'sms_lba_multiplier' => 125.5, 'sms_broadcast_multiplier' => 125.5,
            'sms_targeted_multiplier' => 125.5, 'waba_lba_multiplier' => 250,
            'waba_broadcast_multiplier' => 250, 'waba_targeted_multiplier' => 250,
        ]);
    }
}
