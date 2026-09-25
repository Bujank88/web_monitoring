<?php

namespace Tests\Feature;

use App\Http\Controllers\CanvasserDetailController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CanvasserDetailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'email', 'regional', 'role'] as $c) $t->string($c)->nullable();
        });
        Schema::create('leads_master', function (Blueprint $t) {
            $t->id(); $t->integer('user_id'); $t->string('email'); $t->string('company_name');
        });
        Schema::create('report_balance_top_up', function (Blueprint $t) {
            $t->id(); $t->string('email_client'); $t->dateTime('tgl_transaksi');
            $t->dateTime('paid_date')->nullable(); $t->string('payment_method_name');
            $t->decimal('total_settlement_klien'); $t->decimal('amount')->default(0);
        });
        DB::table('users')->insert(['id'=>1, 'name'=>'Owner', 'role'=>'cvsr']);
        for ($i = 1; $i <= 12; $i++) {
            DB::table('leads_master')->insert(['user_id'=>1, 'email'=>"lead$i@example.test", 'company_name'=>"Lead $i"]);
            $this->transaction("lead$i@example.test", '2026-01-15', $i * 100);
        }
        DB::table('leads_master')->insert(['user_id'=>1, 'email'=>' LEAD12@example.test ', 'company_name'=>'Lead 12']);
        $this->transaction('lead12@example.test', '2025-12-31', 600);
        $this->transaction('lead12@example.test', '2026-02-01', 99999);
        $this->transaction('lead12@example.test', '2026-01-20', 99999, 'Voucher Bonus');
        $this->transaction('other@example.test', '2026-01-20', 99999);
    }

    private function transaction($email, $date, $amount, $method = 'Bank'): void
    {
        DB::table('report_balance_top_up')->insert(['email_client'=>$email, 'tgl_transaksi'=>$date.' 12:00:00',
            'total_settlement_klien'=>$amount, 'payment_method_name'=>$method]);
    }

    private function request(int $actor, string $role = 'cvsr'): Request
    {
        $request = Request::create('/', 'GET', ['user_id'=>1, 'month'=>'2026-01', 'table'=>'top-leads']);
        $user = new User(); $user->id = $actor; $user->role = $role;
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    public function test_top_ten_totals_masking_and_previous_year_month(): void
    {
        $data = app(CanvasserDetailController::class)->topLeads($this->request(1))->getData(true);
        $this->assertCount(10, $data['data']);
        $row = $data['data'][0];
        $this->assertSame('Lead 12', $row['company_name']);
        $this->assertEquals(1200, $row['current_total']);
        $this->assertEquals(600, $row['previous_total']);
        $this->assertEquals(100, $row['mom']);
        $this->assertNull($data['data'][1]['mom']);
        $this->assertStringNotContainsString('@example.test', json_encode($data));
    }

    public function test_owner_and_admin_can_download_masked_csv(): void
    {
        foreach ([$this->request(1), $this->request(99, 'Admin')] as $request) {
            $response = app(CanvasserDetailController::class)->download($request);
            ob_start(); $response->sendContent(); $csv = ob_get_clean();
            $this->assertStringContainsString('Lead 12', $csv);
            $this->assertStringNotContainsString('@example.test', $csv);
        }
    }

    public function test_other_user_cannot_download_even_with_owner_query_parameter(): void
    {
        try {
            app(CanvasserDetailController::class)->download($this->request(2));
            $this->fail('Expected forbidden response');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }
}
