<?php

namespace Tests\Feature;

use App\Http\Controllers\PanenPoinV5Controller;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Tests\TestCase;

class PanenPoinV5Test extends TestCase
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
        (require database_path('migrations/2026_11_20_100000_create_panen_poin_umkm_tables.php'))->up();
        (require database_path('migrations/2026_11_24_100000_allow_agency_owners_in_panen_poin_v5.php'))->up();
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

    public function test_mitra_refresh_is_idempotent_and_visible_only_to_authorized_owners(): void
    {
        DB::table('mitra_sbp')->insert([
            ['email_myads' => ' Agency@Example.test '],
            ['email_myads' => 'agency@example.test'],
        ]);
        DB::table('report_balance_top_up')->insert([
            'email_client' => 'agency@example.test', 'tgl_transaksi' => '2026-11-20 12:00:00', 'total_settlement_klien' => 500000,
        ]);
        $controller = app(PanenPoinV5Controller::class);
        $controller->refreshSummaryPanenPoinV5();
        $controller->refreshSummaryPanenPoinV5();
        $this->assertDatabaseCount('akun_panen_poin_v5', 1);
        $this->assertDatabaseCount('summary_panen_poin_v5', 1);
        $this->assertDatabaseHas('summary_panen_poin_v5', [
            'user_id' => -1, 'nama_canvasser' => 'Agency', 'source' => 'mitra_sbp', 'poin' => 2,
        ]);
        $this->assertSame([], $controller->getReportData(Request::create('/'))->getData(true)['data']);
        $admin = User::find(1);
        $admin->role = 'Admin';
        $this->actingAs($admin);
        $data = $controller->getReportData(Request::create('/'))->getData(true)['data'];
        $this->assertCount(1, $data);
        $this->assertSame('Agency', $data[0]['nama_canvasser']);
        $this->assertSame('Agency', $controller->getAkunData(Request::create('/'))->getData(true)['data'][0]['nama_canvasser']);
    }

    public function test_zero_owner_is_agency_and_existing_mitra_owner_is_preserved(): void
    {
        foreach ([0, 1] as $owner) {
            $email = "client{$owner}@example.test";
            \App\Models\AkunPanenPoinV5::create([
                'user_id' => $owner, 'nama_akun' => 'Client', 'email_client' => $email,
                'password' => 'existing-hash', 'source' => 'user_panen_poin_v5',
            ]);
            DB::table('mitra_sbp')->insert(['email_myads' => $email]);
            DB::table('report_balance_top_up')->insert([
                'email_client' => $email, 'tgl_transaksi' => '2026-11-20', 'total_settlement_klien' => 250000,
            ]);
        }
        $controller = app(PanenPoinV5Controller::class);
        $controller->refreshSummaryPanenPoinV5();
        $this->assertDatabaseHas('summary_panen_poin_v5', ['user_id' => 0, 'nama_canvasser' => 'Agency']);
        $this->assertDatabaseHas('summary_panen_poin_v5', ['user_id' => 1, 'nama_canvasser' => 'Test']);
        $this->assertDatabaseHas('akun_panen_poin_v5', ['user_id' => 1, 'password' => 'existing-hash']);
        $data = $controller->getReportCanvasserData(Request::create('/'))->getData(true)['data'];
        $this->assertEquals(1, $data[0]['jumlah_terdaftar']);
    }
}
