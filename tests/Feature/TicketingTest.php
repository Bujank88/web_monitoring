<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketExcelImporter;
use App\Services\TicketReportService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Tests\TestCase;

class TicketingTest extends TestCase
{
    private array $files = [];

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

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    public function test_only_admin_can_view_and_submit_tickets(): void
    {
        $this->get(route('ticketing.create'))->assertRedirect('/login');
        $this->post(route('ticketing.store'), $this->input())->assertRedirect('/login');
        foreach (['Tsel', 'Treg', 'cvsr', 'PH', 'TCD', 'Internal', 'b2b'] as $role) {
            $this->actingAs($this->user($role));
            $this->get(route('ticketing.create'))->assertRedirect('/');
            $this->post(route('ticketing.store'), $this->input())->assertRedirect('/');
        }
        $this->assertDatabaseCount('tickets', 0);
        $this->actingAs($this->user('Admin'))->get(route('ticketing.create'))
            ->assertOk()->assertSee('Input Ticketing')->assertSee('name="evidence_reference"', false);
    }

    public function test_manual_input_generates_number_and_uses_authenticated_creator(): void
    {
        $admin = $this->user('Admin');
        $this->actingAs($admin)->post(route('ticketing.store'), $this->input() + [
            'ticket_number' => 'FORGED', 'created_by' => 999, 'import_source_key' => 'forged', 'user_name' => 'Forged PIC',
        ])->assertRedirect(route('ticketing.create'))->assertSessionHas('success');
        $ticket = Ticket::firstOrFail();
        $this->assertSame('T0000000001', $ticket->ticket_number);
        $this->assertSame($admin->id, $ticket->created_by);
        $this->assertSame('PIC Test', $ticket->user_name);
        $this->assertNull($ticket->import_source_key);
        $this->assertNull($ticket->channel);
    }

    public function test_closed_tickets_require_result_and_valid_resolution_time(): void
    {
        $this->actingAs($this->user('Admin'));
        $input = array_replace($this->input(), ['status' => 'Closed']);
        $this->post(route('ticketing.store'), $input)->assertSessionHasErrors(['resolved_at', 'resolution_update']);
        $input['resolution_update'] = 'Sudah diperbaiki';
        $input['resolved_at'] = '2026-10-05T08:00';
        $this->post(route('ticketing.store'), $input)->assertSessionHasErrors('resolved_at');
        $input['resolved_at'] = '2026-10-05T10:00';
        $this->post(route('ticketing.store'), $input)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_pic_dropdown_groups_users_by_role_and_keeps_previous_selection(): void
    {
        $admin = $this->user('Admin');
        $pic = $this->user('b2b');
        $this->actingAs($admin)->withSession(['_old_input' => ['pic_user_id' => (string) $pic->id]])
            ->get(route('ticketing.create'))->assertOk()
            ->assertSee('<optgroup label="Admin">', false)
            ->assertSee('<optgroup label="b2b">', false)
            ->assertSee('value="'.$pic->id.'" selected', false)
            ->assertSee($pic->email)
            ->assertSee('minimumResultsForSearch: 0', false);
    }

    public function test_pic_must_be_an_existing_user_and_can_be_from_another_role(): void
    {
        $admin = $this->user('Admin');
        $pic = $this->user('PH');
        $this->actingAs($admin);
        $this->post(route('ticketing.store'), array_replace($this->input(), ['pic_user_id' => 999999]))
            ->assertSessionHasErrors('pic_user_id');
        $this->assertDatabaseCount('tickets', 0);
        $this->post(route('ticketing.store'), array_replace($this->input(), ['pic_user_id' => $pic->id]))
            ->assertSessionHasNoErrors();
        $this->assertSame($pic->name, Ticket::firstOrFail()->user_name);
        $this->assertSame($admin->id, Ticket::firstOrFail()->created_by);
    }

    public function test_invalid_input_is_rejected_without_consuming_ticket_number(): void
    {
        $this->actingAs($this->user('Admin'))->post(route('ticketing.store'), array_replace($this->input(), [
            'request_type' => 'Invalid', 'priority' => 'Invalid', 'resolved_at' => '2026-10-05T10:00',
        ]))->assertSessionHasErrors(['request_type', 'priority', 'resolved_at']);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertSame(1, DB::table('ticket_sequences')->value('next_number'));
    }

    public function test_import_preserves_duplicates_dates_and_missing_values_and_is_repeatable(): void
    {
        $file = $this->workbook([
            ['A' => 'Contoh', 'C' => 45959.5, 'J' => 'Example'],
            ['A' => 'T0000000750', 'C' => 46003.5, 'F' => 'Web', 'J' => 'First', 'N' => '12/13/2025 08.00.00', 'R' => 'L3'],
            ['A' => 'T0000000750', 'C' => 46003.5, 'J' => 'Second'],
            ['A' => 'T0000001087', 'K' => 'T0000001087'],
        ]);
        $importer = app(TicketExcelImporter::class);
        $stats = $importer->import($file, true);
        $this->assertSame(2, $stats['imported']);
        $this->assertSame(1, $stats['empty_rows']);
        $this->assertSame(1, $stats['duplicate_numbers']);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertSame(1, DB::table('ticket_sequences')->value('next_number'));
        $this->assertSame($stats, $importer->import($file, false));
        $first = Ticket::where('ticket_number', 'T0000000750')->firstOrFail();
        $this->assertSame('2025-12-12 12:00:00', $first->requested_at->format('Y-m-d H:i:s'));
        $this->assertSame('2025-12-13 08:00:00', $first->resolved_at->format('Y-m-d H:i:s'));
        $this->assertNull($first->priority);
        $this->assertNull($first->user_name);
        $this->assertSame('L3', $first->handling_level);
        $second = Ticket::where('ticket_number', 'T0000000750-2')->firstOrFail();
        $this->assertSame('T0000000750', $second->original_ticket_number);
        $this->assertSame('Second', $second->import_data['cells']['J']);
        $this->assertSame(2, $importer->import($file, false)['already_imported']);
        $this->assertDatabaseCount('tickets', 2);
        $this->assertSame('T0000000751', Ticket::submit(['requested_at' => '2026-10-05 09:00:00'])->ticket_number);
    }

    public function test_invalid_date_aborts_import(): void
    {
        $file = $this->workbook([
            ['A' => 'T0000000001', 'C' => 46003.5, 'J' => 'Valid'],
            ['A' => 'T0000000002', 'C' => '31/02/2025 08:00:00', 'J' => 'Invalid'],
        ]);
        try {
            app(TicketExcelImporter::class)->import($file, false);
            $this->fail('Invalid date must fail');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('format tanggal', $exception->getMessage());
        }
        $this->assertDatabaseCount('tickets', 0);
        $this->assertSame(1, DB::table('ticket_sequences')->value('next_number'));
    }

    public function test_ticket_number_conflict_rolls_back_entire_import(): void
    {
        Ticket::create(['ticket_number' => 'T0000000002', 'requested_at' => '2026-10-05 09:00:00']);
        $file = $this->workbook([
            ['A' => 'T0000000001', 'C' => 46003.5, 'J' => 'First'],
            ['A' => 'T0000000002', 'C' => 46003.5, 'J' => 'Conflict'],
        ]);
        try {
            app(TicketExcelImporter::class)->import($file, false);
            $this->fail('Conflict must fail');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('sudah digunakan', $exception->getMessage());
        }
        $this->assertDatabaseCount('tickets', 1);
        $this->assertSame(1, DB::table('ticket_sequences')->value('next_number'));
    }

    public function test_list_and_close_are_restricted_to_admin(): void
    {
        $ticket = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => 'Open']);
        $this->get(route('ticketing.index'))->assertRedirect('/login');
        $this->patch(route('ticketing.close', $ticket), ['resolution_update' => 'Done'])->assertRedirect('/login');
        foreach (['Tsel', 'Treg', 'cvsr', 'PH', 'TCD', 'Internal', 'b2b'] as $role) {
            $this->actingAs($this->user($role));
            $this->get(route('ticketing.index'))->assertRedirect('/');
            $this->patch(route('ticketing.close', $ticket), ['resolution_update' => 'Done'])->assertRedirect('/');
        }
        $this->assertSame('Open', $ticket->fresh()->status);
    }

    public function test_list_search_and_status_filters_show_only_matching_tickets(): void
    {
        $open = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => 'Open', 'user_name' => 'Vanessa', 'diagnosis_issue' => '<script>alert(1)</script>']);
        $closed = Ticket::submit(['requested_at' => '2026-01-02 09:00:00', 'status' => 'Closed', 'user_name' => 'Dina']);
        $unknown = Ticket::submit(['requested_at' => '2026-01-03 09:00:00']);
        $this->actingAs($this->user('Admin'));
        $this->get(route('ticketing.index'))->assertOk()
            ->assertSee($open->ticket_number)->assertSee($closed->ticket_number)
            ->assertSee('data-close-url="'.route('ticketing.close', $open).'"', false)
            ->assertDontSee('data-close-url="'.route('ticketing.close', $closed).'"', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
        $this->get(route('ticketing.index', ['q' => 'Vanessa', 'status' => 'Open']))->assertOk()
            ->assertSee($open->ticket_number)->assertDontSee($closed->ticket_number);
        $this->get(route('ticketing.index', ['status' => 'Open']))->assertOk()
            ->assertSee($unknown->ticket_number)->assertSee($open->ticket_number)->assertDontSee($closed->ticket_number);
    }

    public function test_list_paginates_and_preserves_search_filters(): void
    {
        for ($index = 0; $index < 26; $index++) {
            Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => 'Open', 'user_name' => 'PIC Pagination']);
        }
        $this->actingAs($this->user('Admin'))->get(route('ticketing.index', ['q' => 'Pagination', 'status' => 'Open']))
            ->assertOk()->assertViewHas('tickets', fn ($tickets) => $tickets->total() === 26 && $tickets->count() === 25)
            ->assertSee('status=Open', false)->assertSee('page=2', false);
    }

    public function test_closing_open_ticket_records_selected_time_and_preserves_previous_notes(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 12:34:56'));
        $ticket = Ticket::submit([
            'requested_at' => '2026-10-04 09:00:00', 'status' => 'Open',
            'resolution_update' => 'Sedang ditelusuri', 'diagnosis_issue' => 'Original issue',
        ]);
        $this->actingAs($this->user('Admin'))->patch(route('ticketing.close', $ticket), [
            'resolution_update' => 'Sudah diperbaiki', 'status' => 'Pending',
            'resolved_at' => '2026-10-04T10:20:30', 'diagnosis_issue' => 'Forged issue',
        ])->assertRedirect(route('ticketing.index'))->assertSessionHas('success');
        $ticket->refresh();
        $this->assertSame('Closed', $ticket->status);
        $this->assertSame('2026-10-04 10:20:30', $ticket->resolved_at->format('Y-m-d H:i:s'));
        $this->assertStringContainsString('Sedang ditelusuri', $ticket->resolution_update);
        $this->assertStringContainsString('Sudah diperbaiki', $ticket->resolution_update);
        $this->assertStringContainsString('04/10/2026 10:20:30', $ticket->resolution_update);
        $this->assertSame('Original issue', $ticket->diagnosis_issue);
        $this->assertEquals(80.5, app(TicketReportService::class)->build(2026, 10)['average_minutes']);
    }

    public function test_close_time_requires_valid_input_between_request_time_and_now(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 12:00:00'));
        $ticket = Ticket::submit(['requested_at' => '2026-10-04 09:00:37', 'status' => 'Open']);
        $this->actingAs($this->user('Admin'));
        $this->get(route('ticketing.index'))->assertOk()
            ->assertSee('name="resolved_at"', false)
            ->assertSee('data-requested-at="2026-10-04T09:00:37"', false);
        foreach ([null, 'not-a-date', '2026-02-30T10:00', '2026-10-04T09:00:36', '2026-10-06T10:00'] as $time) {
            $this->patch(route('ticketing.close', $ticket), ['resolution_update' => 'Done', 'resolved_at' => $time])
                ->assertSessionHasErrors('resolved_at');
            $this->assertSame('Open', $ticket->fresh()->status);
            $this->assertNull($ticket->fresh()->resolved_at);
        }
        $this->patch(route('ticketing.close', $ticket), ['resolution_update' => 'Done', 'resolved_at' => '2026-10-04T09:00:37'])
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-10-04 09:00:37', $ticket->fresh()->resolved_at->format('Y-m-d H:i:s'));
    }

    public function test_close_rejects_missing_result_and_non_open_or_future_tickets(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 12:00:00'));
        $this->actingAs($this->user('Admin'));
        $open = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => 'Open']);
        $this->patch(route('ticketing.close', $open), ['resolution_update' => '   '])->assertSessionHasErrors(['resolution_update', 'resolved_at']);
        $this->assertSame('Open', $open->fresh()->status);
        foreach (['Closed', 'Pending', 'In Progress'] as $status) {
            $ticket = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => $status]);
            $this->patch(route('ticketing.close', $ticket), ['resolution_update' => 'Done', 'resolved_at' => '2026-10-05T12:00:00'])->assertSessionHasErrors('ticket');
            $this->assertSame($status, $ticket->fresh()->status);
            $this->assertNull($ticket->fresh()->resolved_at);
        }
        $future = Ticket::submit(['requested_at' => '2026-10-06 09:00:00', 'status' => 'Open']);
        $this->patch(route('ticketing.close', $future), ['resolution_update' => 'Done', 'resolved_at' => '2026-10-05T12:00:00'])->assertSessionHasErrors('resolved_at');
        $this->assertSame('Open', $future->fresh()->status);
        $this->patch('/ticketing/999999/close', ['resolution_update' => 'Done', 'resolved_at' => '2026-10-05T12:00:00'])->assertNotFound();
    }

    public function test_repeated_close_does_not_overwrite_the_first_resolution(): void
    {
        $ticket = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => 'Open']);
        $this->actingAs($this->user('Admin'));
        $this->patch(route('ticketing.close', $ticket), ['resolution_update' => 'First result', 'resolved_at' => '2026-01-02T09:00'])->assertSessionHasNoErrors();
        $first = $ticket->fresh();
        $this->patch(route('ticketing.close', $ticket), ['resolution_update' => 'Second result', 'resolved_at' => '2026-01-03T09:00'])->assertSessionHasErrors('ticket');
        $this->assertSame($first->resolution_update, $ticket->fresh()->resolution_update);
        $this->assertTrue($first->resolved_at->eq($ticket->fresh()->resolved_at));
    }

    public function test_date_range_includes_entire_end_day_and_supports_one_sided_filters(): void
    {
        $before = Ticket::submit(['requested_at' => '2026-09-30 23:59:59']);
        $start = Ticket::submit(['requested_at' => '2026-10-01 00:00:00']);
        $end = Ticket::submit(['requested_at' => '2026-10-05 23:59:59']);
        $after = Ticket::submit(['requested_at' => '2026-10-06 00:00:00']);
        $this->actingAs($this->user('Admin'));
        $this->get(route('ticketing.index', ['date_from' => '2026-10-01', 'date_to' => '2026-10-05']))->assertOk()
            ->assertSee($start->ticket_number)->assertSee($end->ticket_number)
            ->assertDontSee($before->ticket_number)->assertDontSee($after->ticket_number);
        $this->get(route('ticketing.index', ['date_to' => '2026-10-05']))->assertOk()
            ->assertSee($before->ticket_number)->assertDontSee($after->ticket_number);
        $this->get(route('ticketing.index', ['date_from' => '2026-10-01']))->assertOk()
            ->assertSee($after->ticket_number)->assertDontSee($before->ticket_number);
        $this->get(route('ticketing.index', ['date_from' => '2026-10-06', 'date_to' => '2026-10-01']))
            ->assertSessionHasErrors('date_to');
        $this->get(route('ticketing.index', ['date_from' => 'invalid']))->assertSessionHasErrors('date_from');
    }

    public function test_unrecorded_statuses_are_normalized_to_open(): void
    {
        $first = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => null]);
        $second = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => '   ']);
        $closed = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'status' => 'Closed']);
        (require database_path('migrations/2026_10_05_000001_set_unrecorded_ticket_status_to_open.php'))->up();
        $this->assertDatabaseHas('tickets', ['id' => $first->id, 'status' => 'Open']);
        $this->assertDatabaseHas('tickets', ['id' => $second->id, 'status' => 'Open']);
        $this->assertDatabaseHas('tickets', ['id' => $closed->id, 'status' => 'Closed']);
    }

    public function test_admin_can_edit_without_changing_ticket_number_creator_or_source_data(): void
    {
        $admin = $this->user('Admin');
        $ticket = Ticket::submit([
            'requested_at' => '2026-10-05 09:00:37', 'status' => 'Open', 'user_name' => 'Legacy PIC',
            'created_by' => $admin->id, 'import_data' => ['source' => 'original'],
        ])->fresh();
        $this->actingAs($admin)->get(route('ticketing.edit', $ticket))->assertOk()
            ->assertSee('Edit tiket '.$ticket->ticket_number)->assertSee('2026-10-05T09:00:37')
            ->assertSee('Legacy PIC');
        $data = array_replace($this->input(), [
            'pic_user_id' => '', 'requested_at' => '2026-10-05T09:00:37',
            'diagnosis_issue' => 'Updated issue', 'version' => $ticket->versionToken(),
            'ticket_number' => 'FORGED', 'created_by' => 999, 'import_data' => ['forged'],
        ]);
        $this->patch(route('ticketing.update', $ticket), $data)->assertRedirect(route('ticketing.index'))->assertSessionHasNoErrors();
        $updated = $ticket->fresh();
        $this->assertSame('Updated issue', $updated->diagnosis_issue);
        $this->assertSame('Legacy PIC', $updated->user_name);
        $this->assertSame($ticket->ticket_number, $updated->ticket_number);
        $this->assertSame($admin->id, $updated->created_by);
        $this->assertSame(['source' => 'original'], $updated->import_data);
        $this->assertSame('09:00:37', $updated->requested_at->format('H:i:s'));
    }

    public function test_edit_enforces_validation_and_rejects_stale_changes(): void
    {
        $ticket = Ticket::submit(['requested_at' => '2026-01-01 09:00:00'])->fresh();
        $this->actingAs($this->user('Admin'));
        $data = $this->input() + ['version' => $ticket->versionToken()];
        $this->patch(route('ticketing.update', $ticket), array_replace($data, ['status' => 'Closed']))
            ->assertSessionHasErrors(['resolved_at', 'resolution_update']);
        $this->patch(route('ticketing.update', $ticket), array_replace($data, ['pic_user_id' => 999999]))
            ->assertSessionHasErrors('pic_user_id');
        $this->patch(route('ticketing.update', $ticket), $data)->assertSessionHasNoErrors();
        $this->patch(route('ticketing.update', $ticket), array_replace($data, ['diagnosis_issue' => 'Stale']))
            ->assertSessionHasErrors('ticket');
        $this->assertNotSame('Stale', $ticket->fresh()->diagnosis_issue);
    }

    public function test_admin_can_delete_using_the_version_from_list_and_number_is_not_reused(): void
    {
        $ticket = Ticket::submit(['requested_at' => '2026-01-01 09:00:00', 'import_data' => ['original']])->fresh();
        $this->actingAs($this->user('Admin'));
        $response = $this->get(route('ticketing.index'))->assertOk();
        $version = $response->viewData('tickets')->first()->versionToken();
        $this->assertSame($ticket->versionToken(), $version);
        $this->delete(route('ticketing.destroy', $ticket), ['version' => $version])->assertRedirect(route('ticketing.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $next = Ticket::submit(['requested_at' => '2026-01-01 09:00:00']);
        $this->assertSame('T0000000002', $next->ticket_number);
    }

    public function test_delete_rejects_stale_version_and_edit_delete_are_admin_only(): void
    {
        $ticket = Ticket::submit(['requested_at' => '2026-01-01 09:00:00'])->fresh();
        $version = $ticket->versionToken();
        $ticket->update(['diagnosis_issue' => 'Changed']);
        $this->actingAs($this->user('Admin'));
        $this->delete(route('ticketing.destroy', $ticket), ['version' => $version])->assertSessionHasErrors('ticket');
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
        foreach (['Tsel', 'Treg', 'cvsr', 'PH', 'TCD', 'Internal', 'b2b'] as $role) {
            $this->actingAs($this->user($role));
            $this->get(route('ticketing.edit', $ticket))->assertRedirect('/');
            $this->patch(route('ticketing.update', $ticket), [])->assertRedirect('/');
            $this->delete(route('ticketing.destroy', $ticket), ['version' => $ticket->fresh()->versionToken()])->assertRedirect('/');
        }
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    private function user(string $role): User
    {
        return User::create(['name' => 'Test '.$role, 'email' => $role.'@example.test', 'password' => 'password', 'role' => $role]);
    }

    private function input(): array
    {
        $pic = User::firstOrCreate(['email' => 'pic@example.test'], [
            'name' => 'PIC Test', 'password' => 'password', 'role' => 'Tsel',
        ]);

        return [
            'pic_user_id' => $pic->id, 'requested_at' => '2026-10-05T09:00',
            'request_type' => 'Complaint', 'complaint_type' => 'Web',
            'account_campaign_id' => 'account@example.test', 'diagnosis_issue' => 'Tidak bisa login',
            'status' => 'Open', 'priority' => 'Low',
        ];
    }

    private function workbook(array $rows): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet()->setTitle('ROW');
        $headers = ['Queue', 'User', 'Time of Request', 'Aging', 'Request Type', 'Complaint Type', 'Channel', 'Method', 'Account or Campaign ID', 'Diagnosis Issue', 'Picture / Evidence', 'Update', 'Status', 'Time of Resolution', 'Priority', 'Length of Resolution'];
        $sheet->fromArray($headers, null, 'A7');
        foreach ($rows as $index => $row) {
            foreach ($row as $column => $value) {
                $sheet->setCellValue($column.($index + 8), $value);
            }
        }
        $file = tempnam(sys_get_temp_dir(), 'ticketing_').'.xlsx';
        // tempnam reserves a base file; retain both paths for cleanup.
        $this->files[] = substr($file, 0, -5);
        $this->files[] = $file;
        (new Xlsx($book))->save($file);
        $book->disconnectWorksheets();

        return $file;
    }
}
