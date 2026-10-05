<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupervisorReportTest extends SupervisorAccessTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('loglogin', function (Blueprint $table) {
            $table->integer('user_id');
            foreach (['tgl', 'nama', 'role', 'email'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('report_balance_top_up', function (Blueprint $table) {
            $table->id();
            foreach (['email_client', 'payment_method_name', 'company_name', 'no_invoice'] as $name) {
                $table->string($name)->nullable();
            }
            $table->dateTime('tgl_transaksi');
            $table->dateTime('paid_date')->nullable();
            $table->decimal('total_settlement_klien', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
        });
        foreach ([3, 4, 6] as $id) {
            DB::table('report_balance_top_up')->insert(['email_client' => "client$id@example.test", 'payment_method_name' => 'Transfer', 'tgl_transaksi' => '2026-10-05 12:00:00', 'paid_date' => '2026-10-05 12:00:00', 'total_settlement_klien' => $id * 100000, 'amount' => $id * 100000, 'no_invoice' => "INV$id", 'company_name' => "Company $id"]);
        }
        foreach (['2026_01_11_182616_create_logbook.php', '2026_01_20_145737_create_logbook_daily.php', '2026_07_23_100000_create_fbm_sof_table.php', '2026_08_06_140000_add_waba_id_to_fbm_sof_table.php', '2026_07_20_100000_create_panen_poin_v3_tables.php', '2026_09_10_100000_create_panen_poin_v4_tables.php', '2026_04_14_000000_create_voucher_owner_history_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::create('mitra_sbp', function (Blueprint $table) {
            $table->id();
            $table->string('email_myads');
        });
        Schema::create('data_voucher', function (Blueprint $table) {
            $table->id();
            $table->string('id_transaksi');
            $table->string('voucher_code');
        });
        foreach ([3, 4, 6] as $id) {
            foreach (['logbook', 'logbook_daily'] as $table) {
                $data = ['leads_master_id' => $id === 3 ? 1 : ($id === 4 ? 2 : 3), 'created_at' => '2026-10-05 12:00:00'];
                if ($table === 'logbook') {
                    $data += ['bulan' => 10, 'tahun' => 2026];
                }
                DB::table($table)->insert($data);
            }
            DB::table('fbm_sof')->insert(['pic' => "User $id", 'sender_name' => "Sender $id", 'nomor_wa' => '081234567890', 'verif_bisnis' => 'No', 'credit_line' => 'No', 'created_at' => '2026-10-05 12:00:00']);
            DB::table('voucher_owner_history')->insert(['voucher_code' => "EXTRA$id", 'owner_name' => "User $id", 'effective_from' => '2026-10-01']);
            DB::table('data_voucher')->insert(['voucher_code' => "EXTRA$id", 'id_transaksi' => "INV$id"]);
            foreach ([3, 4] as $version) {
                DB::table("akun_panen_poin_v$version")->insert(['uuid' => "uuid-$version-$id", 'user_id' => $id, 'nama_akun' => "Account $id", 'email_client' => "client$id@example.test", 'password' => 'hashed', 'source' => 'leads_master']);
                DB::table("summary_panen_poin_v$version")->insert(['user_id' => $id, 'nama_canvasser' => "User $id", 'email_client' => "client$id@example.test", 'poin' => 100, 'period_start' => $version === 4 ? '2026-09-01' : '2026-07-01', 'period_end' => $version === 4 ? '2026-10-09' : '2026-08-10', 'periode_label' => 'Test']);
            }
        }
    }

    public function test_global_reports_use_existing_pages_and_endpoints(): void
    {
        $this->get(route('supervisor.daily'))->assertRedirect(route('daily.topup.channel'));
        $this->get(route('supervisor.canvassers'))->assertRedirect(route('admin.home'));
        $this->get(route('supervisor.referral'))->assertRedirect(route('admin.monitoring.canvasser_voucher'));
        foreach (['daily.topup.channel', 'admin.home', 'admin.monitoring.canvasser_voucher'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (['daily_topup_data', 'daily_topup_by_province_data', 'export.daily_topup', 'export.daily_topup_by_province', 'regional_data', 'export.regional', 'canvasser_voucher_data', 'canvasser_voucher_summary', 'export.canvasser_voucher', 'export.canvasser_voucher_summary', 'topup-canvasser.detail'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $middleware = collect($route->gatherMiddleware())->first(fn ($value) => str_starts_with($value, 'checkrole:'));
            $this->assertContains('Supervisor', explode(',', substr($middleware, strlen('checkrole:'))));
        }
    }

    public function test_logbook_and_sof_only_show_team_data(): void
    {
        foreach (['monthly', 'daily'] as $period) {
            $this->get(route('supervisor.logbook', ['period' => $period, 'month' => '2026-10']))->assertOk()->assertSee('Company 3')->assertDontSee('Company 4')->assertDontSee('Company 6');
            $this->get(route('supervisor.logbook', ['period' => $period, 'month' => '2026-10', 'canvasser' => 4]))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
        }
        $this->get(route('supervisor.sof', ['month' => '2026-10']))->assertOk()->assertSee('Sender 3')->assertDontSee('Sender 4')->assertDontSee('Sender 6');
        DB::table('users')->where('id', 3)->update(['supervisor_id' => 2]);
        foreach (['supervisor.sof'] as $route) {
            $this->get(route($route, ['month' => '2026-10']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
        }
    }

    public function test_supervisor_can_submit_sof_only_for_own_active_member(): void
    {
        $this->get(route('supervisor.sof.create'))->assertOk()->assertViewHas('members', fn ($members) => $members->pluck('id')->all() === [3]);
        $data = ['canvasser_id' => 3, 'sender_name' => 'New Sender', 'nomor_wa' => '081111111', 'verif_bisnis' => 'No', 'pic' => 'User 4'];
        $this->post(route('supervisor.sof.store'), $data)->assertRedirect(route('supervisor.sof'));
        $this->assertDatabaseHas('fbm_sof', ['sender_name' => 'New Sender', 'pic' => 'User 3']);
        $data['canvasser_id'] = 4;
        $this->post(route('supervisor.sof.store'), $data)->assertForbidden();
        DB::table('users')->where('id', 6)->update(['name' => 'User 3']);
        $this->get(route('supervisor.sof', ['month' => '2026-10']))->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
        $data['canvasser_id'] = 3;
        $this->post(route('supervisor.sof.store'), $data)->assertForbidden();
    }

    public function test_campaign_reports_are_global_and_writes_stay_blocked(): void
    {
        foreach ([3, 4] as $v) {
            foreach (['report', 'report-canvasser', 'list-akun'] as $suffix) {
                $this->get(route("panenpoinv$v.$suffix"))->assertOk();
            }
            foreach (['report-data', 'report-canvasser-data', 'akun-data'] as $suffix) {
                $this->getJson(route("panenpoinv$v.$suffix", ['canvasser' => 4]))->assertOk()->assertJsonCount(3, 'data');
            }
            $xlsx = $this->get(route("panenpoinv$v.export"))->assertOk()->streamedContent();
            $file = tempnam(sys_get_temp_dir(), 'supervisor-export-');
            try {
                file_put_contents($file, $xlsx);
                $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();
                $this->assertEqualsCanonicalizing(['client3@example.test', 'client4@example.test', 'client6@example.test'], array_column($sheet->rangeToArray('C4:C6'), 0));
                $this->assertSame(6, $sheet->getHighestDataRow());
            } finally {
                unlink($file);
            }
            $this->post(route("panenpoinv$v.store"), [])->assertRedirect('/');
            $this->get(route("panenpoinv$v.refresh"))->assertRedirect('/');
        }
        foreach (['tips-sales', 'faq-l0'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->actingAs(User::findOrFail(3));
        $this->get(route('supervisor.daily'))->assertRedirect('/');
    }
}
