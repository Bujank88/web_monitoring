<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KamAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Isolated fixtures: no application migrations or production database access.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'role', 'status', 'referral_code', 'nohp', 'password', 'area', 'branch', 'regional'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('leads_master', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('source_id')->nullable();
            $table->integer('sector_id')->nullable();
            foreach (['email', 'myads_account', 'company_name', 'data_type'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('detail_leads_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('leads_master_id');
            $table->integer('user_id');
            foreach (['user_name', 'regional', 'company_name', 'email', 'mobile_phone', 'data_type', 'flag_event'] as $column) {
                $table->string($column)->nullable();
            }
            $table->decimal('total_settlement_klien')->default(0);
            $table->decimal('saldo_utama')->default(0);
            $table->timestamps();
        });
        Schema::create('loglogin', function (Blueprint $table) {
            $table->integer('user_id');
            foreach (['tgl', 'nama', 'role', 'email'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        foreach (['leads_source', 'sectors'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
            });
        }
        Schema::create('regional_provinces', function (Blueprint $table) {
            $table->string('regional');
        });

        foreach ([1 => 'cvsr', 2 => 'cvsr', 3 => 'PH', 4 => 'AM', 5 => 'KAM', 6 => 'Admin'] as $id => $role) {
            DB::table('users')->insert([
                'id' => $id, 'role' => $role, 'name' => "Owner $id", 'email' => "owner$id@example.test",
                'status' => $id === 2 ? 'Belum Aktif' : 'Aktif', 'referral_code' => $id === 1 ? 'AM1' : null,
            ]);
            DB::table('leads_master')->insert([
                'id' => $id, 'user_id' => $id, 'email' => "client$id@example.test",
                'myads_account' => "account$id@example.test", 'company_name' => "Company $id",
                'data_type' => $id === 2 ? 'Eksisting Akun' : 'Leads', 'created_at' => now(),
            ]);
            DB::table('detail_leads_summary')->insert([
                'leads_master_id' => $id, 'user_id' => $id, 'user_name' => "Owner $id",
                'email' => "client$id@example.test", 'company_name' => "Company $id",
                'data_type' => $id === 2 ? 'Eksisting Akun' : 'Leads', 'created_at' => now(),
            ]);
        }
        $this->actingAs(User::findOrFail(5));
    }

    public function test_kam_sees_all_canvasser_leads_including_inactive_owners(): void
    {
        $response = $this->getJson(route('leads-master.data'))->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing([1, 2], array_column($response->json('data'), 'user_id'));
        foreach ($response->json('data') as $row) {
            $this->assertStringContainsString('Lihat', $row['aksi']);
            $this->assertStringNotContainsString('Edit', $row['aksi']);
        }
        $this->getJson(route('leads-master.data', ['canvasser' => 2]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.user_id', 2);
        $this->getJson(route('leads-master.data', ['canvasser' => 3]))->assertOk()->assertJsonCount(0, 'data');
        $csv = $this->get(route('leads-master.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('client1@example.test', $csv);
        $this->assertStringContainsString('client2@example.test', $csv);
        $this->assertStringNotContainsString('client3@example.test', $csv);
        $this->assertStringNotContainsString('client5@example.test', $csv);
        $filtered = $this->get(route('leads-master.export', ['canvasser' => 3]))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('client', $filtered);
    }

    public function test_kam_menu_pages_and_canvasser_filter(): void
    {
        $this->get('/')->assertRedirect(route('daily.topup.channel'));
        $this->get(route('leads-master.index'))->assertOk()
            ->assertViewHas('canvassers', fn ($users) => $users->pluck('id')->sort()->values()->all() === [1, 2])
            ->assertSee('Daily Top Up Channel')->assertSee('Report Canvasser')
            ->assertSee('Data Leads & Akun')->assertSee('Logbook Monthly')->assertSee('Logbook Daily')
            ->assertSee('System Management')->assertSee('Change Password')->assertSee('LOGOUT')
            ->assertDontSee('Manajemen Users')->assertDontSee('New Leads')->assertDontSee('AM Referral');
        foreach (['admin.home', 'daily.topup.channel', 'logbook.index', 'logbook-daily.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        foreach (['regional_data', 'export.regional', 'daily_topup_data', 'daily_topup_by_province_data', 'topup-canvasser.detail.overview', 'logbook-daily.foto'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertTrue(collect($route->gatherMiddleware())->contains(fn ($m) => str_starts_with($m, 'checkrole:') && in_array('KAM', explode(',', substr($m, 10)))), $name);
        }
    }

    public function test_kam_can_only_open_details_and_transactions_owned_by_canvassers(): void
    {
        foreach ([1, 2] as $id) {
            $this->get(route('leads-master.show', $id))->assertOk();
        }
        foreach ([3, 4, 5] as $id) {
            $this->get(route('leads-master.show', $id))->assertForbidden();
        }
        foreach (['client1@example.test', 'account2@example.test'] as $email) {
            $this->get(route('transaction-detail', ['email' => $email]))->assertOk();
        }
        $this->get(route('transaction-detail', ['email' => 'client3@example.test']))->assertForbidden();
        $this->getJson(route('transaction-detail.data', ['email' => 'account4@example.test', 'month' => now()->format('Y-m')]))->assertForbidden();
        $this->get(route('topup-canvasser.detail', ['user_id' => 1, 'month' => now()->format('Y-m')]))->assertOk();
        $this->get(route('topup-canvasser.detail', ['user_id' => 3, 'month' => now()->format('Y-m'), 'source' => 'mpcc']))->assertForbidden();
    }

    public function test_kam_cannot_change_leads_logbooks_or_manage_users(): void
    {
        foreach (['leads-master.create', 'leads-master.create-existing', 'users.page'] as $route) {
            $this->get(route($route))->assertRedirect('/');
        }
        $this->get(route('leads-master.edit', 1))->assertRedirect('/');
        $this->put(route('leads-master.update', 1), ['company_name' => 'Changed'])->assertRedirect('/');
        foreach (['leads-master.store', 'leads-master.store-existing', 'logbook.update', 'logbook.insert', 'logbook.day', 'logbook-daily.realisasiLogbook', 'users.store'] as $route) {
            $this->post(route($route), ['id' => 1])->assertRedirect('/');
        }
        $this->assertDatabaseHas('leads_master', ['id' => 1, 'company_name' => 'Company 1']);
    }

    public function test_kam_logbook_summary_includes_only_canvasser_owners(): void
    {
        Schema::create('logbook_daily', function (Blueprint $table) {
            $table->id();
            $table->integer('leads_master_id');
            $table->string('komitmen');
            $table->integer('plan_min_topup');
            $table->integer('realisasi_topup');
            $table->string('realisasi_photo')->nullable();
        });
        foreach ([1, 2, 3, 4, 5] as $id) {
            DB::table('logbook_daily')->insert([
                'leads_master_id' => $id, 'komitmen' => 'New Leads', 'plan_min_topup' => 100, 'realisasi_topup' => 50,
            ]);
        }
        $this->getJson(route('logbook-daily.summary'))->assertOk()->assertJsonPath('total', 200)->assertJsonPath('real_total', 100);
        $this->getJson(route('logbook-daily.summary', ['canvasser' => 2]))->assertOk()->assertJsonPath('total', 100);
        $response = $this->getJson(route('logbook-daily.summary', ['canvasser' => 3]))->assertOk();
        $this->assertEquals(0, $response->json('total') ?? 0);
        DB::table('logbook_daily')->where('leads_master_id', 3)->update(['realisasi_photo' => 'selfie-logbook/other-role.jpg']);
        $this->get(route('logbook-daily.foto', ['filePath' => 'selfie-logbook/other-role.jpg']))->assertForbidden();
    }

    public function test_admin_can_create_and_assign_kam_and_kam_can_login(): void
    {
        $this->actingAs(User::findOrFail(6));
        $this->postJson(route('users.store'), [
            'name' => 'New KAM', 'email' => 'kam@example.test', 'nohp' => '0812345678', 'role' => 'KAM',
        ])->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'kam@example.test', 'role' => 'KAM']);
        $this->postJson(route('users.update', 4), [
            'name' => 'New KAM', 'email' => 'owner4@example.test', 'nohp' => '0812345678', 'role' => 'KAM',
        ])->assertOk();
        $this->assertDatabaseHas('users', ['id' => 4, 'role' => 'KAM']);
        auth()->logout();
        $this->post(route('login'), ['email' => 'kam@example.test', 'password' => '123456'])
            ->assertRedirect(route('daily.topup.channel'));
        $this->assertAuthenticatedAs(User::where('email', 'kam@example.test')->firstOrFail());
    }
}
