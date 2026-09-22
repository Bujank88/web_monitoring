<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Controllers\SalesAnalysisController;
use App\Services\SalesAnalysisService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesAnalysisTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-09 12:00:00'));
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-09 12:00:00'));
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'role', 'email', 'lokasi_kerja'] as $column) {
                $table->string($column)->nullable();
            }
        });
        Schema::create('leads_master', function (Blueprint $table) {
            $table->id(); $table->integer('user_id'); $table->string('email');
        });
        Schema::create('mitra_sbp', function (Blueprint $table) {
            $table->id(); $table->string('email_myads'); $table->string('remark');
        });
        Schema::create('b2b_clients', function (Blueprint $table) {
            $table->id(); $table->string('myads_account');
        });
        Schema::create('report_balance_top_up', function (Blueprint $table) {
            $table->id(); $table->string('email_client')->nullable(); $table->integer('user_id')->nullable();
            $table->dateTime('tgl_transaksi'); $table->decimal('total_settlement_klien', 18, 2)->nullable();
            $table->string('payment_method_name');
        });
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Canvasser A', 'role' => 'cvsr', 'lokasi_kerja' => 'Jakarta'],
            ['id' => 2, 'name' => 'Canvasser B', 'role' => 'cvsr', 'lokasi_kerja' => 'Bandung'],
            ['id' => 3, 'name' => 'Powerhouse', 'role' => 'PH', 'lokasi_kerja' => null],
            ['id' => 4, 'name' => 'AM', 'role' => 'AM', 'lokasi_kerja' => null],
            ['id' => 5, 'name' => 'MPCC', 'role' => 'MPCC', 'lokasi_kerja' => null],
            ['id' => 6, 'name' => 'No Target', 'role' => 'cvsr', 'lokasi_kerja' => 'Medan'],
        ]);
        foreach ([1 => 'canvas@example.test', 3 => 'ph@example.test', 4 => 'am@example.test', 5 => 'self@example.test', 6 => 'untargeted@example.test'] as $id => $email) {
            DB::table('leads_master')->insert(['user_id' => $id, 'email' => $email]);
        }
        // Duplicate source records must not multiply settlement.
        DB::table('leads_master')->insert(['user_id' => 1, 'email' => ' CANVAS@example.test ']);
        $this->topup('canvas@example.test', 90, '2026-09-01');
        $this->topup('canvas@example.test', 90, '2026-09-09');
        $this->topup('canvas@example.test', 1000, '2026-09-10');
        $this->topup('canvas@example.test', 1000, '2026-09-02', 'Voucher Bonus');
        $this->topup('canvas@example.test', 60, '2026-08-01');
        $this->topup('canvas@example.test', 40, '2026-08-09');
        $this->topup('canvas@example.test', 999, '2026-08-10');
        $this->actingAs(new User(['id' => 99, 'name' => 'Admin', 'role' => 'Admin', 'email' => 'admin@example.test']));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function topup(string $email, float $amount, string $date, string $method = 'Transfer'): void
    {
        DB::table('report_balance_top_up')->insert([
            'email_client' => $email, 'user_id' => 10, 'total_settlement_klien' => $amount,
            'tgl_transaksi' => $date.' 10:00:00', 'payment_method_name' => $method,
        ]);
    }

    private function chart(string $chart, array $filters = [])
    {
        return $this->getJson(route('sales-analysis.data', ['chart' => $chart, 'month' => '2026-09'] + $filters));
    }

    public function test_admin_page_renders_without_loading_report_queries(): void
    {
        DB::enableQueryLog();
        $this->get(route('sales-analysis.index'))->assertOk()->assertSee('Analisa Sales')->assertSee('Tren kumulatif topup')
            ->assertDontSee('Ranking pencapaian canvasser')->assertDontSee('Kebutuhan topup per hari vs aktual');
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_script_loads_through_laravel_with_a_separate_public_directory(): void
    {
        $originalPublicPath = public_path();
        $this->app->usePublicPath(base_path('storage/framework/testing-public-root'));

        try {
            $scriptPath = base_path('public/js/sales-analysis.js');
            $scriptUrl = route('sales-analysis.script', ['v' => filemtime($scriptPath)]);
            $this->get(route('sales-analysis.index'))->assertOk()->assertSee($scriptUrl)
                ->assertDontSee(asset('js/sales-analysis.js'));

            $response = $this->get($scriptUrl)->assertOk()
                ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertSame(realpath($scriptPath), $response->baseResponse->getFile()->getRealPath());
            $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        } finally {
            $this->app->usePublicPath($originalPublicPath);
        }
    }

    public function test_every_chart_and_page_are_admin_only(): void
    {
        $urls = [route('sales-analysis.index'), route('sales-analysis.script')];
        foreach (['trend', 'accounts-trend', 'retention', 'channels'] as $chart) {
            $urls[] = route('sales-analysis.data', ['chart' => $chart, 'month' => '2026-09']);
        }
        foreach (['AM', 'AM Leader', 'cvsr', 'PH', 'Tsel', 'Regional', 'MPCC'] as $role) {
            $this->actingAs(new User(['role' => $role, 'name' => 'Other', 'email' => 'other@example.test']));
            foreach ($urls as $url) {
                $this->get($url)->assertRedirect('/');
            }
            $this->assertStringNotContainsString(route('sales-analysis.index'), view('sidebar')->render());
        }
        auth()->logout();
        foreach ($urls as $url) {
            $this->getJson($url)->assertUnauthorized();
        }
    }

    public function test_trend_uses_equal_cutoffs_and_fills_missing_days(): void
    {
        $data = $this->chart('trend')->assertOk()->json();
        $this->assertCount(9, $data['current']);
        $this->assertCount(9, $data['previous']);
        $this->assertEquals([90, 90, 90, 90, 90, 90, 90, 90, 180], $data['current']);
        $this->assertEquals(100, $data['previous'][8]);
        $this->chart('trend', ['channel' => 'am'])->assertOk()->assertJsonPath('current.8', 0);
        $this->chart('trend', ['channel' => 'canvasser'])->assertOk()->assertJsonPath('current.8', 180);
    }

    public function test_channels_use_unique_accounts_and_deduplicate_lookup_rows(): void
    {
        foreach (['canvas@example.test', 'ph@example.test', 'b2b@example.test', 'b2b@example.test'] as $email) {
            DB::table('b2b_clients')->insert(['myads_account' => $email]);
        }
        foreach (['Agency', 'Agency'] as $remark) {
            DB::table('mitra_sbp')->insert(['email_myads' => 'agency@example.test', 'remark' => $remark]);
        }
        foreach (['ph', 'am', 'b2b', 'agency', 'self'] as $email) {
            $this->topup($email.'@example.test', 20, '2026-09-03');
        }
        $data = $this->chart('channels')->assertOk()->json();
        $rows = collect($data['rows'])->keyBy('channel');
        $this->assertEquals(280, $data['summary']['total']);
        $this->assertEquals(100, $data['summary']['previous']);
        $this->assertEquals(180, $data['summary']['growth']);
        $this->assertSame(6, $data['summary']['accounts']);
        $this->assertEquals(180, $rows['canvasser']['current']);
        $this->assertSame(1, $rows['canvasser']['accounts']);
        $this->assertEquals(40, $rows['b2b']['current']);
        $this->assertEquals(20, $rows['am']['current']);
        $this->assertEquals(20, $rows['agency']['current']);
        $this->assertEquals(20, $rows['self_service']['current']);
        $this->assertEqualsWithDelta(100, $rows->sum('share'), 0.001);
        $this->assertNull($rows['am']['growth']);
    }

    public function test_account_trend_counts_unique_emails_cumulatively_with_equal_cutoffs(): void
    {
        $this->topup(' CANVAS@example.test ', 30, '2026-09-02');
        $this->topup('am@example.test', 40, '2026-09-03');
        $this->topup('am@example.test', 50, '2026-09-08');
        $this->topup('   ', 100, '2026-09-04');
        $this->topup('bonus@example.test', 100, '2026-09-04', 'Voucher Bonus');
        $this->topup('future@example.test', 100, '2026-09-10');
        $this->topup('after-cutoff@example.test', 100, '2026-08-10');
        $data = $this->chart('accounts-trend')->assertOk()->json();
        $this->assertSame([1, 1, 2, 2, 2, 2, 2, 2, 2], $data['current']);
        $this->assertSame(array_fill(0, 9, 1), $data['previous']);
        $this->chart('accounts-trend', ['channel' => 'am'])->assertOk()
            ->assertJsonPath('current.0', 0)->assertJsonPath('current.2', 1)->assertJsonPath('current.8', 1);
        $this->chart('accounts-trend', ['channel' => 'canvasser'])->assertOk()->assertJsonPath('current.8', 1);
        $this->chart('accounts-trend', ['channel' => 'powerhouse'])->assertOk()->assertJsonPath('current.8', 0);
        $this->chart('channels')->assertOk()->assertJsonPath('summary.accounts', 2);
        $this->chart('trend')->assertOk()->assertJsonPath('current.8', 400);
    }

    public function test_retention_tracks_monthly_cohorts_from_january_without_requiring_consecutive_topups(): void
    {
        DB::table('report_balance_top_up')->delete();
        foreach (['a', 'b', 'c'] as $email) {
            $this->topup($email.'@example.test', 10, '2025-12-31');
        }
        $this->topup(' A@example.test ', 20, '2025-12-25');
        foreach (['a', 'b', 'new'] as $email) {
            $this->topup($email.'@example.test', 10, '2026-01-15');
        }
        $this->topup('a@example.test', 30, '2026-01-20');
        $this->topup('c@example.test', 10, '2026-01-20', 'Voucher Bonus');
        $this->topup('  ', 10, '2025-12-25');
        $this->topup('  ', 10, '2026-01-15');
        $this->topup('b@example.test', 10, '2026-02-15');
        $this->topup('new@example.test', 10, '2026-02-15');
        $this->topup('a@example.test', 10, '2026-08-31');
        $this->topup('b@example.test', 10, '2026-08-01');
        $this->topup('a@example.test', 10, '2026-09-01');
        $this->topup('a@example.test', 10, '2026-09-09');
        $this->topup('new-september@example.test', 10, '2026-09-09');
        $this->topup('b@example.test', 10, '2026-09-10');

        $response = $this->chart('retention')->assertOk()->assertJsonCount(9, 'rows');
        $response->assertJsonPath('offsets', range(0, 8))
            ->assertJsonPath('rows.0.month', '2026-01')->assertJsonPath('rows.0.baseline', 3)
            ->assertJsonPath('rows.0.cells.0.rate', 100)->assertJsonPath('rows.0.cells.1.count', 2)
            ->assertJsonPath('rows.0.cells.1.rate', 66.67)->assertJsonPath('rows.0.cells.2.rate', 0)
            ->assertJsonPath('rows.0.cells.7.rate', 66.67)->assertJsonPath('rows.0.cells.8.rate', 33.33)
            ->assertJsonPath('rows.0.cells.8.is_partial', true)
            ->assertJsonPath('rows.1.baseline', 2)->assertJsonPath('rows.1.cells.0.rate', 100)
            ->assertJsonPath('rows.1.cells.8.rate', null)->assertJsonPath('rows.1.cells.8.is_future', true)
            ->assertJsonPath('rows.2.baseline', 0)->assertJsonPath('rows.2.cells.0.rate', null)
            ->assertJsonPath('rows.7.baseline', 2)->assertJsonPath('rows.7.cells.1.rate', 50)
            ->assertJsonPath('rows.8.baseline', 2)->assertJsonPath('rows.8.cells.0.rate', 100)
            ->assertJsonPath('rows.8.cells.0.is_partial', true);
        $this->assertStringNotContainsString('@example.test', $response->getContent());
        $this->getJson(route('sales-analysis.data', ['chart' => 'retention', 'month' => '2026-01']))
            ->assertOk()->assertJsonCount(1, 'rows')->assertJsonPath('rows.0.cells.0.is_partial', false)->assertJsonPath('rows.0.cells.0.rate', 100);
    }

    public function test_retention_always_uses_all_channels_and_reuses_the_same_cache(): void
    {
        $this->topup('am@example.test', 10, '2026-08-31');
        $this->chart('retention')->assertOk()->assertJsonPath('rows.7.cells.1.rate', 50);
        $this->chart('retention', ['channel' => 'am'])->assertOk()->assertJsonPath('rows.7.baseline', 2)->assertJsonPath('rows.7.cells.1.rate', 50);
        $this->chart('retention', ['channel' => 'canvasser'])->assertOk()->assertJsonPath('rows.7.cells.1.rate', 50);
        $this->chart('retention', ['channel' => 'powerhouse'])->assertOk()->assertJsonPath('rows.7.cells.1.rate', 50);
        DB::enableQueryLog();
        $this->chart('retention', ['channel' => 'am'])->assertOk();
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_stale_cache_is_returned_before_refreshing_data(): void
    {
        $key = 'sales-analysis:v1:channels:2026-09:2026-09-09:all';
        Cache::put($key, ['rows' => [], 'updated_at' => 'old result'], 3600);
        Cache::put('illuminate:cache:flexible:created:'.$key, now()->subMinutes(6)->timestamp, 3600);
        $calls = 0;
        $service = \Mockery::mock(SalesAnalysisService::class)->makePartial();
        $service->shouldReceive('channels')->once()->andReturnUsing(function () use (&$calls) {
            $calls++;
            return ['rows' => [['name' => 'AM']]];
        });
        $request = \Illuminate\Http\Request::create('/sales-analysis/data/channels', 'GET', ['month' => '2026-09']);
        $response = (new SalesAnalysisController())->data($request, 'channels', $service);
        $this->assertSame('old result', $response->getData(true)['updated_at']);
        $this->assertSame(0, $calls);
        app(\Illuminate\Support\Defer\DeferredCallbackCollection::class)->invoke();
        $this->assertSame(1, $calls);
        $this->assertSame('AM', Cache::get($key)['rows'][0]['name']);
    }

    public function test_completed_month_and_short_previous_month_are_handled(): void
    {
        $this->getJson(route('sales-analysis.data', ['chart' => 'trend', 'month' => '2026-08']))
            ->assertOk()->assertJsonCount(31, 'current')->assertJsonPath('current.30', 1099);
        $this->getJson(route('sales-analysis.data', ['chart' => 'trend', 'month' => '2026-03']))
            ->assertOk()->assertJsonCount(31, 'current')->assertJsonCount(28, 'previous');
        $this->getJson(route('sales-analysis.data', ['chart' => 'accounts-trend', 'month' => '2026-03']))
            ->assertOk()->assertJsonCount(31, 'current')->assertJsonCount(28, 'previous');
    }

    public function test_filters_are_validated(): void
    {
        foreach (['2026-13', 'invalid', '2026-10', '2026-09-01'] as $month) {
            $this->getJson(route('sales-analysis.data', ['chart' => 'trend', 'month' => $month]))->assertUnprocessable();
        }
        $this->chart('trend', ['channel' => 'invalid'])->assertUnprocessable();
        $this->getJson('/sales-analysis/data/unknown?month=2026-09')->assertNotFound();
    }

    public function test_chart_requests_are_isolated_and_cached_separately(): void
    {
        $this->chart('trend')->assertOk();
        $this->chart('channels')->assertOk();
        DB::enableQueryLog();
        $this->chart('trend')->assertOk();
        $this->assertSame([], DB::getQueryLog());
        DB::disableQueryLog();
        // A different channel must not receive the cached all-channel series.
        $this->chart('trend', ['channel' => 'am'])->assertOk()->assertJsonPath('current.8', 0);
    }

    public function test_empty_month_returns_zero_totals_without_division_errors(): void
    {
        DB::table('report_balance_top_up')->delete();
        $this->chart('channels')->assertOk()->assertJsonPath('summary.total', 0)
            ->assertJsonPath('summary.accounts', 0)->assertJsonPath('summary.growth', null);
        $this->chart('trend')->assertOk()->assertJsonPath('current.8', 0);
        $this->chart('accounts-trend')->assertOk()->assertJsonPath('current.8', 0);
        $this->chart('retention')->assertOk()->assertJsonPath('rows.0.cells.0.rate', null)->assertJsonPath('rows.7.cells.1.rate', null);
    }
}
