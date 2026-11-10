<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\RestrictProductAccess;
use App\Models\User;
use App\Support\ProductAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'nohp', 'role', 'status', 'area', 'branch', 'regional', 'referral_code', 'remember_token'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('loglogin', function (Blueprint $table) {
            $table->integer('user_id');
            foreach (['tgl', 'nama', 'role', 'email'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        $this->actingAs(User::create([
            'name' => 'Product', 'email' => 'product@example.test', 'password' => 'product-password',
            'role' => 'product', 'status' => 'Aktif',
        ]));
    }

    public function test_product_can_open_dashboard_and_only_requested_menus(): void
    {
        $this->get('/')->assertRedirect(route('daily.topup.channel'));
        $response = $this->get(route('daily.topup.channel'))->assertOk();
        foreach (['Daily Top Up Channel', 'Report Canvasser', 'Analisa Sales', 'MPCC', 'Powerhouse',
            'AM Referral', 'Mitra SBP', 'Program Campaign', 'Change Password', 'Logout'] as $label) {
            $response->assertSee($label);
        }
        foreach (['powerhouse.2m.input-leads', 'users.page', 'panenpoinv4.index', 'leads-master.create'] as $name) {
            $response->assertDontSee('href="'.route($name).'"', false);
        }
        $this->get(route('admin.home'))->assertOk();
        $this->get(route('sales-analysis.index'))->assertOk();
    }

    public function test_report_data_and_exports_pass_both_access_middleware(): void
    {
        $routes = [...ProductAccess::REPORT_ROUTES];
        foreach (['panenpoin', 'panenpoinv2', 'panenpoinv3', 'panenpoinv4'] as $prefix) {
            foreach (['report', 'report-data', 'report-canvasser', 'report-canvasser-data', 'report-ph', 'report-ph-data', 'export', 'list-akun', 'akun-data'] as $suffix) {
                $routes[] = "$prefix.$suffix";
            }
        }
        foreach ($routes as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $request = Request::create('/report');
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => auth()->user());
            $response = (new RestrictProductAccess)->handle($request, fn ($request) =>
                (new CheckRole)->handle($request, fn () => response('allowed'), 'Admin'));
            $this->assertSame('allowed', $response->getContent(), $name);
        }
        $request = Request::create('/topup-canvasser/data');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));
        $this->assertTrue(ProductAccess::allows($request));
    }

    public function test_direct_input_update_refresh_and_unrelated_menu_access_is_denied(): void
    {
        foreach (['powerhouse.2m.input-leads', 'panenpoinv4.index', 'panenpoinv4.refresh',
            'users.page', 'leads-master.create', 'tips-sales', 'admin.upload'] as $name) {
            $this->get(route($name))->assertForbidden();
        }
        $this->post(route('powerhouse.2m.store'), [])->assertForbidden();
        $this->post(route('panenpoinv4.store'), [])->assertForbidden();
        $this->post(route('users.store'), [])->assertForbidden();
        $this->get('/summary-padi-umkm')->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_login_password_change_and_logout_work(): void
    {
        auth()->logout();
        $this->post(route('login'), ['email' => 'product@example.test', 'password' => 'product-password'])
            ->assertRedirect(route('daily.topup.channel'));
        $this->get(route('change-password'))->assertOk();
        $this->post(route('password.update'), [
            'current_password' => 'product-password', 'current_password_confirmation' => 'product-password',
            'new_password' => 'new-product-password', 'new_password_confirmation' => 'new-product-password',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('new-product-password', User::first()->password));
        $this->assertGuest();
        $this->actingAs(User::first())->get(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_create_command_creates_exactly_one_account_and_keeps_existing_credentials(): void
    {
        $this->artisan('users:create-product')->assertSuccessful();
        $account = User::where('email', 'product@telkomsel.co.id')->firstOrFail();
        $this->assertSame('product', $account->role);
        $this->assertSame('Aktif', $account->status);
        $this->assertNotEmpty($account->password);
        $this->artisan('users:create-product')->assertFailed();
        $this->assertSame($account->password, $account->fresh()->password);
        $this->assertDatabaseCount('users', 2);
    }
}
