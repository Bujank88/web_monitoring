<?php

namespace Tests\Feature;

use App\Http\Controllers\OneSynergyReportController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OneSynergyMonitoringSaldoTest extends TestCase
{
    public function test_transfers_fund_campaign_spending_and_monthly_balances(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        config(['database.connections.kam_myads' => config('database.connections.sqlite')]);
        DB::purge('kam_myads');
        Schema::connection('kam_myads')->create('merchant_campaign_mappings', function (Blueprint $table) {
            $table->string('merchant_id'); $table->string('campaign_id');
        });
        DB::connection('kam_myads')->table('merchant_campaign_mappings')->insert([
            'merchant_id' => 'CH778899', 'campaign_id' => '1',
        ]);

        Schema::create('transaksi_balance_transfer', function (Blueprint $table) {
            $table->string('id_klien_pengirim');
            $table->string('email_penerima');
            $table->dateTime('tanggal');
            $table->decimal('jumlah');
        });
        Schema::create('one_synergy_reports', function (Blueprint $table) {
            $table->string('id_iklan')->default('1');
            $table->date('tgl_tayang');
            $table->integer('sukses')->default(0);
            $table->string('kategori_iklan')->default('WABA');
            $table->string('tipe_kanal')->default('BROADCAST');
            $table->integer('total_harga')->nullable();
        });
        Schema::create('one_synergy_monthly_multipliers', function (Blueprint $table) {
            $table->string('month');
            $table->decimal('waba_broadcast_multiplier', 15, 2)->nullable();
        });
        DB::table('one_synergy_monthly_multipliers')->insert([
            ['month' => '2026-08', 'waba_broadcast_multiplier' => 100],
            ['month' => '2026-09', 'waba_broadcast_multiplier' => 25],
            ['month' => '2026-10', 'waba_broadcast_multiplier' => 25],
        ]);
        Schema::create('report_balance_top_up', function (Blueprint $table) {
            $table->string('email_client');
            $table->dateTime('tgl_transaksi');
            $table->decimal('amount');
        });

        foreach (['2026-08-31 12:00:00' => 1000, '2026-09-01 00:00:00' => 500, '2026-10-01 00:00:00' => 200] as $date => $amount) {
            DB::table('transaksi_balance_transfer')->insert([
                'id_klien_pengirim' => 'OTHER-SENDER',
                'email_penerima' => ' ARIEF_AZHAR@PTKAM.CO.ID ', 'tanggal' => $date, 'jumlah' => $amount,
            ]);
        }
        DB::table('transaksi_balance_transfer')->insert([
            'id_klien_pengirim' => 'REG-DO-000000661407', 'email_penerima' => 'other@example.com',
            'tanggal' => '2026-09-02 00:00:00', 'jumlah' => 99999,
        ]);
        DB::table('one_synergy_reports')->insert([
            ['tgl_tayang' => '2026-08-31', 'total_harga' => 0, 'sukses' => 1],
            ['tgl_tayang' => '2026-09-01', 'total_harga' => 0, 'sukses' => 8],
            ['tgl_tayang' => '2026-09-30', 'total_harga' => 0, 'sukses' => 2],
            ['tgl_tayang' => '2026-10-01', 'total_harga' => 0, 'sukses' => 1],
        ]);
        DB::table('one_synergy_reports')->insert([
            'id_iklan' => 'not-whitelisted', 'tgl_tayang' => '2026-09-01', 'total_harga' => 999999, 'sukses' => 100000,
        ]);
        DB::table('report_balance_top_up')->insert([
            'email_client' => 'arief_azhar@ptkam.co.id',
            'tgl_transaksi' => '2026-09-01 00:00:00', 'amount' => 9000,
        ]);

        $method = new \ReflectionMethod(OneSynergyReportController::class, 'monitoringSaldoHistory');
        $controller = new OneSynergyReportController();
        $history = $method->invoke($controller, '2026-09');
        $this->assertEquals(900, $history['opening_balance']);
        $this->assertEquals(500, $history['total_in']);
        $this->assertEquals(250, $history['total_out']);
        $this->assertEquals(1150, $history['ending_balance']);
        $this->assertEquals(1325, $history['remaining_balance']);
        $this->assertCount(3, $history['rows']);
        $this->assertEquals(1150, end($history['rows'])['running_balance']);
        $this->assertSame('Masuk', $history['rows'][0]['transaction_type']);
        $this->assertSame('Balance Transfer', $history['rows'][0]['source']);
        $this->assertSame('Keluar', $history['rows'][1]['transaction_type']);
        $this->assertSame('Balance Terpakai Report 1Synergy', $history['rows'][1]['source']);

        DB::table('one_synergy_monthly_multipliers')->where('month', '2026-09')->update(['waba_broadcast_multiplier' => 50]);
        $repriced = $method->invoke($controller, '2026-09');
        $this->assertEquals(500, $repriced['total_out']);
        $this->assertEquals(900, $repriced['opening_balance']);
        $this->assertEquals(900, $repriced['ending_balance']);
        $this->assertEquals(1075, $repriced['remaining_balance']);
        DB::table('one_synergy_monthly_multipliers')->where('month', '2026-09')->update(['waba_broadcast_multiplier' => 25]);

        $empty = $method->invoke($controller, '2026-07');
        $this->assertSame([], $empty['rows']);
        $this->assertEquals(0, $empty['ending_balance']);

        DB::table('transaksi_balance_transfer')->delete();
        DB::table('one_synergy_reports')->delete();
        DB::table('transaksi_balance_transfer')->insert([
            'id_klien_pengirim' => 'REG-DO-000000661407', 'email_penerima' => 'arief_azhar@ptkam.co.id',
            'tanggal' => '2026-09-01 00:00:00', 'jumlah' => 100000000,
        ]);
        $funded = $method->invoke($controller, '2026-09');
        $this->assertEquals(100000000, $funded['total_in']);
        $this->assertEquals(0, $funded['total_out']);
        $this->assertEquals(100000000, $funded['remaining_balance']);

        DB::table('one_synergy_reports')->insert(['tgl_tayang' => '2026-09-02', 'total_harga' => 0, 'sukses' => 600000]);
        $spent = $method->invoke($controller, '2026-09');
        $this->assertEquals(100000000, $spent['total_in']);
        $this->assertEquals(15000000, $spent['total_out']);
        $this->assertEquals(85000000, $spent['ending_balance']);
        $this->assertEquals(85000000, $spent['remaining_balance']);

        Schema::create('loglogin', function (Blueprint $table) {
            $table->integer('user_id');
            foreach (['tgl', 'nama', 'role', 'email'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        $user = new \App\Models\User();
        $user->forceFill(['id' => 1, 'name' => 'Synergy', 'email' => 'synergy@example.com', 'role' => '1synergy']);
        $this->actingAs($user);
        $request = \Illuminate\Http\Request::create('/', 'GET', ['month' => '2026-09']);
        try {
            $controller->monitoringSaldo($request);
            $this->fail('1Synergy must not access Monitoring Saldo.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        $user->role = 'Admin';
        $admin = $controller->monitoringSaldo($request)->getData();
        $this->assertTrue($admin['canViewIncomingBalance']);
        $this->assertEquals(100000000, $admin['totalIn']);
        $this->assertEquals(85000000, $admin['remainingBalance']);
        $this->assertCount(2, $admin['historyRows']);
        $this->assertEquals(0, $admin['openingBalance']);
        $this->assertEquals(85000000, $admin['endingBalance']);

        DB::table('one_synergy_monthly_multipliers')->where('month', '2026-09')->update(['waba_broadcast_multiplier' => null]);
        $unpriced = $method->invoke($controller, '2026-09');
        $this->assertNull($unpriced['total_out']);
        $this->assertNull($unpriced['ending_balance']);
        $this->assertNull($unpriced['remaining_balance']);
        $this->assertNull($unpriced['rows'][1]['running_balance']);
        $this->assertEquals(100000000, $unpriced['total_in']);
        $this->assertEquals(0, $unpriced['opening_balance']);
        DB::table('one_synergy_monthly_multipliers')->where('month', '2026-09')->update(['waba_broadcast_multiplier' => 0]);
        $free = $method->invoke($controller, '2026-09');
        $this->assertEquals(0, $free['total_out']);
        $this->assertEquals(100000000, $free['ending_balance']);
    }
}
