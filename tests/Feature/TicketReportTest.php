<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketReportService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('role');
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_05_000000_create_tickets_table.php'))->up();
    }

    public function test_report_is_admin_only_and_renders_all_three_report_tabs(): void
    {
        $this->get(route('ticketing.report'))->assertRedirect('/login');
        foreach (['Tsel', 'Treg', 'cvsr', 'PH', 'Internal', 'TCD', 'b2b'] as $role) {
            $this->actingAs($this->user($role))->get(route('ticketing.report'))->assertRedirect('/');
        }
        $this->actingAs($this->user('Admin'))->get(route('ticketing.report'))->assertOk()
            ->assertSee('PVT Channel')->assertSee('PVT Complaint Type')->assertSee('Ringkasan Bulanan')
            ->assertSee('Tidak ada tiket pada periode ini');
    }

    public function test_week_buckets_and_resolution_thresholds_match_excel(): void
    {
        foreach ([0, 15, 16, 60, 61, 1440, 1441] as $index => $minutes) {
            $this->ticket('2026-09-'.str_pad((string) [1, 7, 8, 14, 15, 21, 30][$index], 2, '0', STR_PAD_LEFT).' 09:00:00', $minutes);
        }
        $report = app(TicketReportService::class)->build(2026, 9);
        $this->assertSame([2, 2, 2, 1, 7], $this->row($report['channel'][0], 'Grand Total')['cells']);
        $categories = $report['summary'][0];
        $this->assertSame(2, $this->last($this->row($categories, '(Hari yang sama) Cepat')));
        $this->assertSame(2, $this->last($this->row($categories, '(Hari yang sama) Sedang')));
        $this->assertSame(2, $this->last($this->row($categories, '(Hari yang sama) Lambat')));
        $this->assertSame(1, $this->last($this->row($categories, 'Lebih dari 1 Hari')));
        $this->assertSame(7, $report['year_total']);
    }

    public function test_average_is_weighted_and_rates_use_total_ticket_counts(): void
    {
        $this->ticket('2026-09-01 09:00:00', 10);
        $this->ticket('2026-09-02 09:00:00', 20);
        $this->ticket('2026-09-08 09:00:00', 90);
        $this->ticket('2026-09-09 09:00:00', null, 'Open');
        $report = app(TicketReportService::class)->build(2026, 9);
        $average = $this->row($report['channel'][1], 'Grand Total')['cells'];
        $this->assertEquals([15, 90, null, null, 40], $average);
        $this->assertEquals(75, $report['resolution_rate']);
        $this->assertEquals(75, $this->last($this->row($report['summary'][3], 'Closed')));
        $this->assertEquals(25, $this->last($this->row($report['summary'][3], 'Open')));
        $this->assertEquals(100, $this->last($this->row($report['summary'][3], 'Grand Total')));
        $this->assertSame(4, $this->last($this->row($report['summary'][2], 'Grand Total')));
    }

    public function test_unresolved_invalid_dates_and_blank_groups_remain_visible(): void
    {
        $this->ticket('2026-09-01 09:00:00', null, 'Pending', null, null);
        $this->ticket('2026-09-02 09:00:00', -5, 'Closed', null, null);
        $this->ticket('2026-09-03 09:00:00', null, 'Closed');
        $this->ticket('2026-09-04 09:00:00', null, 'In Progress');
        $report = app(TicketReportService::class)->build(2026, 9);
        $this->assertSame(2, $report['invalid_times']);
        $this->assertSame(2, $report['ongoing']);
        $this->assertSame(2, $this->last($this->row($report['summary'][0], 'Sedang Berlangsung')));
        $this->assertSame(2, $this->last($this->row($report['summary'][0], 'Periksa Waktu')));
        $this->assertEquals(-5, $report['average_minutes']);
        $this->assertEquals(-5, $this->last($this->row($report['channel'][1], '(blank)')));
        $this->assertSame(2, $this->last($this->row($report['complaint'][0], '(blank)')));
    }

    public function test_year_month_and_inclusive_date_range_filter_the_correct_tables(): void
    {
        $this->ticket('2025-09-01 09:00:00', 10);
        $this->ticket('2026-01-01 09:00:00', 10);
        $this->ticket('2026-09-01 00:00:00', 10);
        $this->ticket('2026-09-05 23:59:59', 10);
        $this->ticket('2026-09-06 00:00:00', 10);
        $this->ticket('2026-10-01 09:00:00', 10);
        $service = app(TicketReportService::class);
        $full = $service->build(2026, 9);
        $this->assertSame(5, $full['year_total']);
        $this->assertSame(3, $full['weekly_total']);
        $range = $service->build(2026, 9, '2026-09-01', '2026-09-05');
        $this->assertSame(2, $range['year_total']);
        $this->assertSame(2, $range['weekly_total']);
        $this->assertSame(2, $this->last($this->row($range['summary'][0], 'Grand Total')));
        $this->assertSame(16, count($range['summary'][0]['columns']));
    }

    public function test_default_period_uses_latest_ticket_and_changes_appear_immediately(): void
    {
        $ticket = $this->ticket('2026-09-01 09:00:00', null, 'Open');
        $this->actingAs($this->user('Admin'));
        $this->get(route('ticketing.report'))->assertOk()
            ->assertViewHas('filters', fn ($filters) => $filters['year'] === 2026 && $filters['month'] === 9)
            ->assertViewHas('report', fn ($report) => $report['ongoing'] === 1 && $report['closed'] === 0);
        $ticket->update(['status' => 'Closed', 'resolved_at' => '2026-09-01 09:15:00']);
        $this->get(route('ticketing.report'))->assertOk()
            ->assertViewHas('report', fn ($report) => $report['closed'] === 1 && (float) $report['average_minutes'] === 15.0);
        $ticket->delete();
        $this->get(route('ticketing.report'))->assertOk()->assertViewHas('report', fn ($report) => $report['year_total'] === 0);
    }

    public function test_filters_reject_invalid_periods_and_empty_averages_are_not_zero(): void
    {
        $this->actingAs($this->user('Admin'));
        $this->get(route('ticketing.report', ['month' => 13]))->assertSessionHasErrors('month');
        $this->get(route('ticketing.report', ['year' => 1999]))->assertSessionHasErrors('year');
        $this->get(route('ticketing.report', ['date_from' => '2026-09-05', 'date_to' => '2026-09-01']))->assertSessionHasErrors('date_to');
        $empty = app(TicketReportService::class)->build(2026, 9);
        $this->assertNull($empty['average_minutes']);
        $this->assertNull($empty['resolution_rate']);
    }

    public function test_summary_cards_follow_year_month_and_date_filters(): void
    {
        $this->ticket('2025-09-01 09:00:00', 500);
        $this->ticket('2026-01-01 09:00:00', 1000);
        $this->ticket('2026-09-01 09:00:00', 10);
        $this->ticket('2026-09-05 23:59:59', 30);
        $this->ticket('2026-09-06 09:00:00', null, 'Open');
        $this->ticket('2026-10-01 09:00:00', 90);
        $this->actingAs($this->user('Admin'));

        $september = $this->get(route('ticketing.report', ['year' => 2026, 'month' => 9]))->assertOk();
        $report = $september->viewData('report');
        $this->assertSame(3, $report['filtered_total']);
        $this->assertSame(2, $report['closed']);
        $this->assertSame(1, $report['ongoing']);
        $this->assertEqualsWithDelta(200 / 3, $report['resolution_rate'], 0.0001);
        $this->assertEquals(20, $report['average_minutes']);
        $september->assertSee('66,67%')->assertSee('20,00 menit');

        $range = $this->get(route('ticketing.report', [
            'year' => 2026, 'month' => 9, 'date_from' => '2026-09-01', 'date_to' => '2026-09-05',
        ]))->assertOk()->viewData('report');
        $this->assertSame(2, $range['filtered_total']);
        $this->assertSame(2, $range['closed']);
        $this->assertSame(0, $range['ongoing']);
        $this->assertEquals(100, $range['resolution_rate']);
        $this->assertEquals(20, $range['average_minutes']);

        $october = $this->get(route('ticketing.report', ['year' => 2026, 'month' => 10]))->assertOk()->viewData('report');
        $this->assertSame(1, $october['filtered_total']);
        $this->assertEquals(90, $october['average_minutes']);
        $previousYear = $this->get(route('ticketing.report', ['year' => 2025, 'month' => 9]))->assertOk()->viewData('report');
        $this->assertSame(1, $previousYear['filtered_total']);
        $this->assertEquals(500, $previousYear['average_minutes']);
    }

    public function test_empty_selected_month_does_not_show_annual_totals_in_cards(): void
    {
        $this->ticket('2026-01-01 09:00:00', 10);
        $this->actingAs($this->user('Admin'))->get(route('ticketing.report', ['year' => 2026, 'month' => 9]))
            ->assertOk()->assertSee('Tidak ada tiket pada periode ini')
            ->assertViewHas('report', fn ($report) => $report['year_total'] === 1
                && $report['filtered_total'] === 0 && $report['closed'] === 0 && $report['ongoing'] === 0
                && $report['resolution_rate'] === null && $report['average_minutes'] === null);
    }

    private function ticket(string $requestedAt, ?int $minutes, string $status = 'Closed', ?string $channel = 'WABA', ?string $complaint = 'Campaign Creation'): Ticket
    {
        return Ticket::submit([
            'requested_at' => $requestedAt, 'status' => $status, 'channel' => $channel, 'complaint_type' => $complaint,
            'resolved_at' => $minutes === null ? null : Carbon::parse($requestedAt)->addMinutes($minutes),
        ]);
    }

    private function user(string $role): User
    {
        return User::create(['name' => 'Test '.$role, 'email' => $role.'@example.test', 'password' => 'password', 'role' => $role]);
    }

    private function row(array $pivot, string $label): array
    {
        return collect($pivot['rows'])->firstWhere('label', $label);
    }

    private function last(array $row): int|float|null
    {
        return $row['cells'][array_key_last($row['cells'])];
    }
}
