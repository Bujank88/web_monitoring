<?php

namespace Tests\Feature;

use App\Http\Controllers\BackController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PowerhouseReferralPerformanceTest extends TestCase
{
    public function test_bounded_queries_preserve_totals_and_month_boundaries(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('STR_TO_DATE', fn ($value, $format) => $value ? substr($value, 0, 10) : null, 2);
        DB::statement('CREATE TABLE users (id INTEGER, name TEXT, role TEXT)');
        DB::statement('CREATE TABLE leads_master (id INTEGER, user_id INTEGER, email TEXT COLLATE NOCASE, created_at TEXT)');
        DB::statement('CREATE TABLE bookings (nama TEXT, tanggal TEXT)');
        DB::statement('CREATE TABLE data_registarsi_status_approveorreject (email TEXT COLLATE NOCASE, status TEXT, tanggal_approval_aktivasi TEXT)');
        DB::statement('CREATE TABLE report_balance_top_up (id INTEGER, email_client TEXT COLLATE NOCASE, amount REAL, tgl_transaksi TEXT, payment_method_name TEXT)');
        DB::statement('CREATE TABLE saldo_transfer (id INTEGER, email_client TEXT COLLATE NOCASE, amount REAL, tgl_transaksi TEXT)');
        DB::table('users')->insert([['id' => 1, 'name' => 'Team PH', 'role' => 'PH'], ['id' => 2, 'name' => 'Empty PH', 'role' => 'PH']]);
        DB::table('leads_master')->insert([
            ['id' => 1, 'user_id' => 1, 'email' => 'New@example.test', 'created_at' => '2026-08-01 00:00:00'],
            ['id' => 2, 'user_id' => 1, 'email' => 'old@example.test', 'created_at' => '2026-01-01 00:00:00'],
        ]);
        DB::table('data_registarsi_status_approveorreject')->insert([
            ['email' => 'new@example.test', 'status' => 'APPROVE', 'tanggal_approval_aktivasi' => '2026-08-01'],
            ['email' => 'old@example.test', 'status' => 'APPROVE', 'tanggal_approval_aktivasi' => '2026-01-01'],
        ]);
        foreach ([
            ['new@example.test', 100, '2026-08-01 00:00:00'],
            ['old@example.test', 200, '2026-08-31 23:59:59'],
            ['old@example.test', 300, '2026-09-01 00:00:00'],
            ['old@example.test', 400, '2026-07-15 23:59:59'],
            ['old@example.test', 500, '2026-07-16 00:00:00'],
            ['old@example.test', 600, '2026-06-30 23:59:59'],
        ] as $i => [$email, $amount, $date]) {
            DB::table('report_balance_top_up')->insert(['id' => $i + 1, 'email_client' => $email, 'amount' => $amount, 'tgl_transaksi' => $date, 'payment_method_name' => 'Bank']);
            DB::table('saldo_transfer')->insert(['id' => $i + 1, 'email_client' => $email, 'amount' => $amount / 10, 'tgl_transaksi' => $date]);
        }
        DB::table('report_balance_top_up')->insert(['id' => 99, 'email_client' => 'new@example.test', 'amount' => 9000, 'tgl_transaksi' => '2026-08-01', 'payment_method_name' => 'Voucher Bonus']);
        DB::enableQueryLog();
        $method = new \ReflectionMethod(BackController::class, 'buildPowerHouseDealTopupMomResult');
        $result = $method->invoke(new BackController, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'), fn () => collect(), Carbon::parse('2026-08-15'));
        $row = collect($result)->firstWhere('team_powerhouse', 'Team PH');
        $this->assertEquals(330, $row['total_topup']);
        $this->assertEquals(100, $row['top_up_new_akun_rp']);
        $this->assertEquals(200, $row['top_up_existing_akun_rp']);
        $this->assertEquals(30, $row['total_transfer_saldo_rp']);
        $this->assertEquals(4, $row['jumlah_akun']);
        $this->assertEquals(2, $row['deal_topup_new_akun']);
        $this->assertEquals(2, $row['deal_topup_existing_akun']);
        $this->assertEquals(440, $row['mom_prev_partial']);
        $this->assertEquals(550, $row['mom_prev_remaining']);
        $this->assertEquals(110, $row['mom_current_partial']);
        $this->assertSame('31 Aug 2026', $row['tgl_transaksi_terakhir']);
        $this->assertEquals(0, collect($result)->firstWhere('team_powerhouse', 'Empty PH')['total_topup']);
        $queries = collect(DB::getQueryLog())->pluck('query');
        $transactionQueries = $queries->filter(fn ($sql) => str_contains($sql, 'report_balance_top_up') || str_contains($sql, '"saldo_transfer" as'));
        $this->assertCount(8, $transactionQueries);
        foreach ($transactionQueries as $sql) {
            $this->assertStringContainsString('"tgl_transaksi" >= ?', $sql);
            $this->assertStringContainsString('"tgl_transaksi" < ?', $sql);
        }

        // Duplicate leads must keep the original join multiplicity: sums grow,
        // but distinct account and new-transaction counts must not grow.
        DB::table('leads_master')->insert([
            'id' => 3, 'user_id' => 1, 'email' => 'NEW@example.test',
            'created_at' => '2026-08-01 00:00:00',
        ]);
        $result = $method->invoke(new BackController, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'), fn () => collect(), Carbon::parse('2026-08-15'));
        $row = collect($result)->firstWhere('team_powerhouse', 'Team PH');
        $this->assertEquals(440, $row['total_topup']);
        $this->assertEquals(200, $row['top_up_new_akun_rp']);
        $this->assertEquals(40, $row['total_transfer_saldo_rp']);
        $this->assertEquals(4, $row['jumlah_akun']);
        $this->assertEquals(2, $row['deal_topup_new_akun']);
        $this->assertEquals(220, $row['mom_current_partial']);
    }
}
