<?php

namespace Tests\Feature;

use App\Http\Controllers\AmReferralController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AmLeaderAccessTest extends TestCase
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

        foreach ([1 => 'AM', 2 => 'AM', 3 => 'PH', 4 => 'cvsr', 5 => 'AM Leader', 6 => 'Admin'] as $id => $role) {
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

    public function test_leader_sees_all_am_leads_and_accounts_only(): void
    {
        $response = $this->getJson(route('leads-master.data'))->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing([1, 2], array_column($response->json('data'), 'user_id'));
        foreach ($response->json('data') as $row) {
            $this->assertStringContainsString('Lihat', $row['aksi']);
            $this->assertStringNotContainsString('Edit', $row['aksi']);
        }
        $this->getJson(route('leads-master.data', ['canvasser' => 2]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.user_id', 2);
        $this->getJson(route('leads-master.data', ['canvasser' => 3]))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_export_obeys_am_scope_and_owner_filter(): void
    {
        $csv = $this->get(route('leads-master.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('client1@example.test', $csv);
        $this->assertStringContainsString('client2@example.test', $csv);
        $this->assertStringNotContainsString('client3@example.test', $csv);
        $this->assertStringNotContainsString('client5@example.test', $csv);
        $filtered = $this->get(route('leads-master.export', ['canvasser' => 3]))->assertOk()->streamedContent();
        $this->assertStringNotContainsString('client', $filtered);
    }

    public function test_leader_menu_and_am_filter_are_available(): void
    {
        $this->get('/')->assertRedirect(route('am.referral.index'));
        $this->get(route('leads-master.index'))->assertOk()
            ->assertViewHas('canvassers', fn ($users) => $users->pluck('id')->sort()->values()->all() === [1, 2])
            ->assertSee('Daily Top Up Channel')->assertSee('AM Referral')->assertSee('Data Leads & Akun', false)
            ->assertDontSee('href="'.route('leads-master.create').'"', false)
            ->assertDontSee('href="'.route('leads-master.create-existing').'"', false);
        $this->get(route('am.referral.index'))->assertOk();
        $this->get(route('daily.topup.channel'))->assertOk();
    }

    public function test_leader_can_view_am_details_but_cannot_read_other_roles(): void
    {
        $this->get(route('leads-master.show', 1))->assertOk();
        $this->get(route('leads-master.show', 2))->assertOk();
        $this->get(route('leads-master.show', 3))->assertForbidden();
        $this->get(route('leads-master.show', 5))->assertForbidden();
        foreach (['client1@example.test', 'account2@example.test'] as $email) {
            $this->get(route('transaction-detail', ['email' => $email]))->assertOk();
        }
        $this->get(route('transaction-detail', ['email' => 'client3@example.test']))->assertForbidden();
        $this->getJson(route('transaction-detail.data', ['email' => 'account3@example.test', 'month' => now()->format('Y-m')]))
            ->assertForbidden();
    }

    public function test_leader_cannot_create_or_edit_leads_using_direct_requests(): void
    {
        foreach (['leads-master.create', 'leads-master.create-existing'] as $route) {
            $this->get(route($route))->assertRedirect('/');
        }
        foreach (['leads-master.store', 'leads-master.store-existing'] as $route) {
            $this->post(route($route), ['user_id' => 1])->assertRedirect('/');
        }
        $this->get(route('leads-master.edit', 1))->assertRedirect('/');
        $this->put(route('leads-master.update', 1), ['company_name' => 'Changed'])->assertRedirect('/');
        $this->assertDatabaseCount('leads_master', 6);
        $this->assertDatabaseHas('leads_master', ['id' => 1, 'company_name' => 'Company 1']);
    }

    public function test_regular_am_and_admin_keep_their_existing_leads_scope(): void
    {
        $this->actingAs(User::findOrFail(1));
        $this->getJson(route('leads-master.data'))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.user_id', 1);
        $this->getJson(route('leads-master.data', ['canvasser' => 2]))->assertOk()->assertJsonPath('data.0.user_id', 1);
        $this->get(route('leads-master.show', 2))->assertForbidden();
        $this->get(route('leads-master.create'))->assertOk();
        $this->get(route('transaction-detail', ['email' => 'client1@example.test']))->assertOk();
        $this->actingAs(User::findOrFail(6));
        $this->getJson(route('leads-master.data'))->assertOk()->assertJsonCount(6, 'data');
    }

    public function test_referral_scope_includes_all_am_for_leader_and_only_self_for_am(): void
    {
        $method = new \ReflectionMethod(AmReferralController::class, 'amUsers');
        $controller = new AmReferralController;
        $this->assertSame([1, 2], $method->invoke($controller)->pluck('id')->all());
        $this->actingAs(User::findOrFail(1));
        $this->assertSame([1], $method->invoke($controller)->pluck('id')->all());
    }

    public function test_admin_can_create_and_assign_leader_without_referral_code(): void
    {
        $this->actingAs(User::findOrFail(6));
        $this->postJson(route('users.store'), [
            'name' => 'New Leader', 'email' => 'leader@example.test', 'nohp' => '0812345678', 'role' => 'AM Leader',
        ])->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'leader@example.test', 'role' => 'AM Leader', 'referral_code' => null]);
        $this->postJson(route('users.update', 2), [
            'name' => 'Promoted Leader', 'email' => 'owner2@example.test', 'nohp' => '0812345678', 'role' => 'AM Leader',
        ])->assertOk();
        $this->assertDatabaseHas('users', ['id' => 2, 'role' => 'AM Leader', 'referral_code' => null]);
    }

    public function test_leader_login_redirects_to_am_referral(): void
    {
        DB::table('users')->where('id', 5)->update(['password' => bcrypt('leader-password')]);
        auth()->logout();

        $this->post(route('login'), ['email' => 'owner5@example.test', 'password' => 'leader-password'])
            ->assertRedirect(route('am.referral.index'));
        $this->assertAuthenticatedAs(User::findOrFail(5));
    }
}
