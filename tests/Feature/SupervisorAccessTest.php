<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupervisorAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'nohp', 'role', 'status', 'area', 'branch', 'regional', 'referral_code'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_05_000000_add_supervisor_id_to_users_table.php'))->up();
        Schema::create('leads_master', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            foreach (['company_name', 'email', 'nama', 'mobile_phone', 'data_type', 'remarks'] as $column) {
                $table->string($column)->nullable();
            }
            $table->integer('plan_min_topup')->default(0);
            $table->timestamps();
        });
        foreach ([1 => 'Supervisor', 2 => 'Supervisor', 3 => 'cvsr', 4 => 'cvsr', 5 => 'Admin', 6 => 'cvsr'] as $id => $role) {
            DB::table('users')->insert(['id' => $id, 'name' => "User $id", 'email' => "user$id@example.test", 'role' => $role, 'status' => 'Aktif']);
        }
        DB::table('users')->where('id', 3)->update(['supervisor_id' => 1]);
        DB::table('users')->where('id', 4)->update(['supervisor_id' => 2]);
        foreach ([3, 4, 6] as $id) {
            DB::table('leads_master')->insert(['user_id' => $id, 'company_name' => "Company $id", 'email' => "client$id@example.test", 'data_type' => 'Leads', 'created_at' => '2026-10-05 12:00:00']);
        }
        $this->actingAs(User::findOrFail(1));
    }

    public function test_dashboard_and_filters_cannot_expose_other_teams(): void
    {
        $this->get('/')->assertRedirect(route('supervisor.index'));
        $this->get(route('supervisor.index'))->assertOk()->assertSee('Company 3')->assertDontSee('Company 4')->assertDontSee('Company 6')
            ->assertSee('href="'.route('supervisor.index').'"', false)
            ->assertSee('href="'.route('change-password').'"', false)
            ->assertSee('href="'.route('logout').'"', false)
            ->assertDontSee('href="'.route('leads-master.index').'"', false)
            ->assertDontSee('href="'.route('leads-master.create').'"', false)
            ->assertDontSee('href="'.route('leads-master.create-existing').'"', false)
            ->assertDontSee('<p>Jadwal</p>', false)
            ->assertSee('href="'.route('supervisor.logbook', 'daily').'"', false)
            ->assertSee('href="'.route('supervisor.sof').'"', false)
            ->assertViewHas('summary', fn ($summary) => $summary->keys()->all() === [3]);
        $this->get(route('supervisor.index', ['canvasser' => 4]))->assertOk()->assertViewHas('leads', fn ($leads) => $leads->total() === 0);
        $this->get(route('supervisor.index', ['q' => 'client4']))->assertOk()->assertDontSee('Company 4');
        $this->get(route('supervisor.index', ['from' => '2026-10-05', 'to' => '2026-10-05']))->assertOk()->assertSee('Company 3');
        $this->get(route('supervisor.index', ['from' => '2026-10-06']))->assertOk()->assertDontSee('Company 3');
        $this->getJson(route('supervisor.index', ['from' => 'invalid']))->assertUnprocessable();
    }

    public function test_empty_team_is_empty_and_non_canvasser_is_excluded(): void
    {
        DB::table('users')->where('id', 3)->update(['role' => 'PH']);
        $this->get(route('supervisor.index'))->assertOk()->assertDontSee('Company 3')->assertSee('Belum ada anggota binaan');
    }

    public function test_supervisor_cannot_manage_team_or_modify_leads(): void
    {
        $this->get(route('supervisor.team'))->assertRedirect('/');
        $this->post(route('supervisor.team.assign'), ['canvasser_id' => 4, 'supervisor_id' => 1])->assertRedirect('/');
        $this->post(route('leads-master.store'), [])->assertRedirect('/');
        $this->put(route('leads-master.update', 1), ['company_name' => 'Changed'])->assertRedirect('/');
        $this->getJson(route('leads-master.data'))->assertRedirect('/');
        $this->assertDatabaseHas('users', ['id' => 4, 'supervisor_id' => 2]);
    }

    public function test_admin_can_assign_move_and_unassign_members(): void
    {
        $this->actingAs(User::findOrFail(5));
        $this->get(route('supervisor.team'))->assertOk()->assertSee('User 3');
        $this->post(route('supervisor.team.assign'), ['canvasser_id' => 3, 'supervisor_id' => 2])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => 3, 'supervisor_id' => 2]);
        $this->postJson(route('supervisor.team.assign'), ['canvasser_id' => 5, 'supervisor_id' => 2])->assertUnprocessable();
        $this->postJson(route('supervisor.team.assign'), ['canvasser_id' => 3, 'supervisor_id' => 4])->assertUnprocessable();
        $this->post(route('supervisor.team.assign'), ['canvasser_id' => 3, 'supervisor_id' => null])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => 3, 'supervisor_id' => null]);
        $this->actingAs(User::findOrFail(1));
        $this->get(route('supervisor.index'))->assertDontSee('Company 3');
        $this->actingAs(User::findOrFail(3));
        $this->get(route('supervisor.index'))->assertRedirect('/');
    }

    public function test_admin_can_create_supervisor_role(): void
    {
        $this->actingAs(User::findOrFail(5));
        $this->postJson(route('users.store'), ['name' => 'PIC', 'email' => 'pic@example.test', 'nohp' => '081234567890', 'role' => 'Supervisor'])->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'pic@example.test', 'role' => 'Supervisor']);
    }

    public function test_roster_import_creates_five_canvassers_and_is_repeatable(): void
    {
        $command = $this->artisan('canvassers:import-interns');
        $command->assertSuccessful();
        $command->run();
        foreach (\App\Console\Commands\ImportInternCanvassers::ROSTER as [$name, $phone, $email]) {
            $this->assertDatabaseHas('users', ['name' => $name, 'email' => $email, 'nohp' => $phone, 'role' => 'cvsr', 'supervisor_id' => null]);
            $this->assertTrue(\Illuminate\Support\Facades\Hash::check('123456', User::where('email', $email)->first()->password));
        }
        $this->artisan('canvassers:import-interns')->assertSuccessful();
        $this->assertDatabaseCount('users', 11);
    }
}
