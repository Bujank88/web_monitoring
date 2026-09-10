<?php

namespace Tests\Feature;

use App\Http\Controllers\PanenPoinV4Controller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Tests\TestCase;

class PanenPoinV4Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('role');
            $t->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Test', 'email' => 'owner@example.test', 'role' => 'cvsr']);
        (require database_path('migrations/2026_07_20_100000_create_panen_poin_v3_tables.php'))->up();
        DB::table('user_panen_poin_v3')->insert(['user_id' => 1, 'nama_pelanggan' => 'Client', 'akun_myads_pelanggan' => 'client@example.test']);
        DB::table('akun_panen_poin_v3')->insert(['uuid' => 'test-uuid', 'user_id' => 1, 'nama_akun' => 'Client', 'email_client' => 'client@example.test', 'password' => 'hashed-password', 'source' => 'user_panen_poin_v3']);
        (require database_path('migrations/2026_09_10_100000_create_panen_poin_v4_tables.php'))->up();
        Schema::create('leads_master', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('email'); $t->string('mobile_phone')->nullable();
        });
        Schema::create('report_balance_top_up', function (Blueprint $t) {
            $t->string('email_client'); $t->dateTime('tgl_transaksi'); $t->decimal('total_settlement_klien', 15, 2);
        });
        Schema::create('data_paket_seasonal', function (Blueprint $t) {
            $t->string('email'); $t->string('name'); $t->timestamps();
        });
        Schema::create('panen_poin_package_v2', function (Blueprint $t) {
            $t->string('code'); $t->integer('point');
        });
        Schema::create('mitra_sbp', function (Blueprint $t) {
            $t->id(); $t->string('email_myads');
        });
        Schema::create('loglogin', function (Blueprint $t) {
            $t->integer('user_id');
            foreach (['tgl', 'nama', 'role', 'email'] as $column) { $t->string($column); }
            $t->timestamps();
        });
        $this->actingAs(User::find(1));
    }

    public function test_migration_copies_participants_without_copying_points_or_redeems(): void
    {
        $this->assertDatabaseCount('akun_panen_poin_v4', 1);
        $this->assertDatabaseHas('akun_panen_poin_v4', ['source' => 'user_panen_poin_v4', 'password' => 'hashed-password']);
        $this->assertDatabaseCount('user_panen_poin_v4', 1);
        $this->assertDatabaseCount('summary_panen_poin_v4', 0);
        $this->assertDatabaseCount('prize_redeems_v4', 0);
        $this->assertDatabaseCount('akun_panen_poin_v3', 1);
    }

    public function test_refresh_includes_both_boundary_days_and_is_idempotent(): void
    {
        foreach (['2026-08-31 23:59:59', '2026-09-01 00:00:00', '2026-10-09 23:59:59', '2026-10-10 00:00:00'] as $date) {
            DB::table('report_balance_top_up')->insert(['email_client' => 'client@example.test', 'tgl_transaksi' => $date, 'total_settlement_klien' => 250000]);
            DB::table('data_paket_seasonal')->insert(['email' => 'client@example.test', 'name' => 'TEST', 'created_at' => $date]);
        }
        DB::table('panen_poin_package_v2')->insert(['code' => 'TEST', 'point' => 5]);
        $controller = app(PanenPoinV4Controller::class);
        $controller->refreshSummaryPanenPoinV4();
        $controller->refreshSummaryPanenPoinV4();
        $this->assertDatabaseCount('summary_panen_poin_v4', 1);
        $this->assertDatabaseHas('summary_panen_poin_v4', ['total_settlement' => 500000, 'poin' => 2, 'poin_package' => 10, 'poin_akumulasi' => 0, 'period_start' => '2026-09-01', 'period_end' => '2026-10-09']);
        $this->assertDatabaseCount('summary_panen_poin_v3', 0);
        foreach (['getReportData', 'getReportCanvasserData', 'getReportPowerhouseData', 'getAkunData'] as $method) {
            $response = $controller->$method(Request::create('/'));
            $this->assertSame(200, $response->getStatusCode());
            $this->assertArrayNotHasKey('error', $response->getData(true));
        }
        $this->assertSame('/panen-poin-v4/input', route('panenpoinv4.index', [], false));
    }

    public function test_input_creates_only_v4_records_and_repeated_input_is_safe(): void
    {
        $controller = app(PanenPoinV4Controller::class);
        $request = Request::create('/panen-poin-v4/store', 'POST', [
            'nama_pelanggan' => 'New Client',
            'akun_myads_pelanggan' => 'new@example.test',
            'nomor_hp_pelanggan' => '081234567890',
        ]);
        foreach ([1, 2] as $attempt) {
            $response = $controller->store($request);
            $this->assertSame(route('panenpoinv4.index'), $response->getTargetUrl());
            $this->assertFalse(session()->has('error'));
        }
        $this->assertDatabaseCount('akun_panen_poin_v4', 2);
        $this->assertDatabaseCount('user_panen_poin_v4', 2);
        $this->assertDatabaseCount('akun_panen_poin_v3', 1);
        $response = $controller->export(Request::create('/'));
        ob_start();
        $response->sendContent();
        $xlsx = ob_get_clean();
        $this->assertStringStartsWith('PK', $xlsx);
        $this->assertStringContainsString('01_Sep_2026_-_09_Okt_2026', $response->headers->get('content-disposition'));
    }

    public function test_every_v3_route_and_controller_method_has_a_v4_equivalent(): void
    {
        $routes = app('router')->getRoutes();
        $count = 0;
        foreach ($routes as $route) {
            if (!str_starts_with($route->getName() ?? '', 'panenpoinv3.')) { continue; }
            $v4 = $routes->getByName(str_replace('v3', 'v4', $route->getName()));
            $this->assertNotNull($v4);
            $this->assertSame($route->methods(), $v4->methods());
            $this->assertSame($route->gatherMiddleware(), $v4->gatherMiddleware());
            $this->assertSame(str_replace('V3', 'V4', $route->getActionName()), $v4->getActionName());
            $count++;
        }
        $this->assertSame(12, $count);
        foreach ((new \ReflectionClass(\App\Http\Controllers\PanenPoinV3Controller::class))->getMethods() as $method) {
            $this->assertTrue(method_exists(PanenPoinV4Controller::class, str_replace('V3', 'V4', $method->name)));
        }
    }

    public function test_all_five_pages_render_for_each_authorized_role(): void
    {
        $this->withoutExceptionHandling();
        foreach (['Admin', 'cvsr', 'PH'] as $role) {
            $user = User::find(1);
            $user->role = $role;
            $this->actingAs($user);
            foreach (['input', 'report', 'report-canvasser', 'report-ph', 'list-akun'] as $page) {
                $response = $this->get('/panen-poin-v4/' . $page);
                $response->assertOk()->assertSee('Panen Poin V4');
                if (str_starts_with($page, 'report')) {
                    $response->assertSee('01 Sep 2026 - 09 Okt 2026');
                    $response->assertDontSee('Report PanenPoinV2');
                }
            }
        }
    }

    public function test_another_owners_email_returns_validation_instead_of_sql_error(): void
    {
        DB::table('users')->insert(['id' => 2, 'name' => 'Other', 'email' => 'other@example.test', 'role' => 'cvsr']);
        $this->actingAs(User::find(2));
        $this->from('/panen-poin-v4/input')->post('/panen-poin-v4/store', [
            'nama_pelanggan' => 'Client', 'akun_myads_pelanggan' => 'client@example.test', 'nomor_hp_pelanggan' => '08123456789',
        ])->assertRedirect('/panen-poin-v4/input')->assertSessionHasErrors('akun_myads_pelanggan');
        $this->assertDatabaseCount('akun_panen_poin_v4', 1);
        $this->assertDatabaseCount('user_panen_poin_v4', 1);
    }

    public function test_filters_and_canvasser_isolation_through_http(): void
    {
        DB::table('report_balance_top_up')->insert(['email_client' => 'client@example.test', 'tgl_transaksi' => '2026-09-15', 'total_settlement_klien' => 250000]);
        app(PanenPoinV4Controller::class)->refreshSummaryPanenPoinV4();
        $this->getJson('/panen-poin-v4/report-data?source=user_panen_poin_v4&remark=Rookie')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.poin_sisa', 1);
        foreach (['source=leads_master', 'remark=Champion'] as $filter) {
            $this->getJson('/panen-poin-v4/report-data?' . $filter)->assertOk()->assertJsonCount(0, 'data');
        }
        DB::table('users')->insert(['id' => 2, 'name' => 'Other', 'email' => 'other@example.test', 'role' => 'cvsr']);
        $this->actingAs(User::find(2));
        $this->getJson('/panen-poin-v4/report-data')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/panen-poin-v4/akun-data')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_failed_summary_refresh_rolls_back_the_new_participant(): void
    {
        $controller = new class extends PanenPoinV4Controller {
            protected function refreshSummaryForSingleUser($userId, $emailClient): void
            {
                throw new \RuntimeException('Simulated refresh failure');
            }
        };
        $response = $controller->store(Request::create('/panen-poin-v4/store', 'POST', [
            'nama_pelanggan' => 'Rollback Client', 'akun_myads_pelanggan' => 'rollback@example.test', 'nomor_hp_pelanggan' => '08123456789',
        ]));
        $this->assertSame(302, $response->getStatusCode());
        $this->assertTrue(session()->has('error'));
        $this->assertStringNotContainsString('Simulated', session('error'));
        $this->assertDatabaseCount('akun_panen_poin_v4', 1);
        $this->assertDatabaseCount('user_panen_poin_v4', 1);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_redeem_updates_points_and_stock_and_rejects_insufficient_points(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-15 12:00:00', 'Asia/Jakarta'));
        Schema::create('prizes_v2', function (Blueprint $t) {
            $t->id(); $t->integer('point'); $t->integer('stock'); $t->timestamps();
        });
        DB::table('prizes_v2')->insert(['id' => 1, 'point' => 2, 'stock' => 3]);
        DB::table('report_balance_top_up')->insert(['email_client' => 'client@example.test', 'tgl_transaksi' => '2026-09-15', 'total_settlement_klien' => 750000]);
        $controller = app(PanenPoinV4Controller::class);
        $controller->refreshSummaryPanenPoinV4();
        $request = Request::create('/', 'POST', ['akun_id' => 1, 'prize_id' => 1]);
        $this->assertSame(200, $controller->redeemPrize($request)->getStatusCode());
        $this->assertDatabaseHas('summary_panen_poin_v4', ['poin' => 3, 'poin_redeem' => 2]);
        $this->assertDatabaseHas('prizes_v2', ['stock' => 2]);
        $this->assertDatabaseCount('prize_redeems_v4', 1);
        $this->assertDatabaseCount('prize_redeems_v3', 0);
        $this->assertSame(422, $controller->redeemPrize($request)->getStatusCode());
        $this->assertDatabaseCount('prize_redeems_v4', 1);
        $this->assertDatabaseHas('prizes_v2', ['stock' => 2]);
        $controller->refreshSummaryPanenPoinV4();
        $this->assertDatabaseHas('summary_panen_poin_v4', ['poin' => 3, 'poin_redeem' => 2]);
        $this->travelBack();
    }

    public function test_v2_and_v3_pages_still_render_and_notification_copy_matches_v4(): void
    {
        $this->withoutExceptionHandling();
        foreach (['v2', 'v3'] as $version) {
            foreach (['input', 'report', 'report-canvasser', 'report-ph', 'list-akun'] as $page) {
                $this->get('/panen-poin-' . $version . '/' . $page)->assertOk();
            }
        }
        $this->withSession(['success' => 'Akun sudah ada di Panen Poin V4.', 'is_existing_account' => true])
            ->get('/panen-poin-v4/input')->assertOk()->assertDontSee('Notifikasi akun telah dikirim');
    }

    public function test_only_v4_has_an_active_panen_poin_refresh_schedule(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->filter(fn ($event) => str_starts_with($event->description ?? '', 'refreshSummaryPanenPoin'))
            ->values();
        $this->assertCount(1, $events);
        $this->assertSame('refreshSummaryPanenPoinV4', $events[0]->description);
        $this->assertSame('*/5 * * * *', $events[0]->expression);
    }

}
