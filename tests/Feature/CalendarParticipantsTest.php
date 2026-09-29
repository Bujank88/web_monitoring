<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CalendarParticipant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CalendarParticipantsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role');
        });
        (require database_path('migrations/2026_01_17_021223_create_bookings.php'))->up();
        (require database_path('migrations/2026_09_29_000000_create_calendar_participants_table.php'))->up();

        DB::table('users')->insert([
            ['name' => 'Calendar Admin', 'role' => 'Admin'],
            ['name' => 'PH Baru', 'role' => 'PH'],
            ['name' => 'MPCC Baru', 'role' => 'MPCC'],
            ['name' => 'Sony Widjaya', 'role' => 'PH'],
            ['name' => 'Canvasser Lain', 'role' => 'cvsr'],
        ]);
        $this->actingAs(User::first());
    }

    public function test_calendar_combines_saved_names_with_ph_and_mpcc_without_duplicates(): void
    {
        $response = $this->get('/calendar')->assertOk();
        $options = $response->viewData('participants');

        $this->assertSame(12, CalendarParticipant::count());
        $this->assertCount(14, $options);
        foreach (['PH Baru', 'MPCC Baru', 'Robert J. Nandjong', 'Sony Widjaya'] as $name) {
            $response->assertSee('value="'.$name.'"', false);
        }
        $this->assertFalse($options->has('Calendar Admin'));
        $this->assertFalse($options->has('Canvasser Lain'));
        $this->assertSame(1, substr_count($response->getContent(), 'value="Sony Widjaya"'));

        $groups = $response->viewData('participantGroups');
        $this->assertSame(['PH', 'MPCC', 'Nama tambahan'], $groups->keys()->all());
        $this->assertSame(['PH Baru', 'Sony Widjaya'], $groups['PH']->keys()->all());
        $this->assertSame(['MPCC Baru'], $groups['MPCC']->keys()->all());
        $this->assertTrue($groups['Nama tambahan']->has('Robert J. Nandjong'));
        $this->assertFalse($groups['Nama tambahan']->has('Sony Widjaya'));
        $response->assertSee('<optgroup label="PH (Powerhouse)">', false)
            ->assertSee('<optgroup label="MPCC">', false)
            ->assertSee('<optgroup label="Nama tambahan">', false);
    }

    public function test_new_accounts_and_saved_names_appear_without_changing_code(): void
    {
        $this->get('/calendar')->assertOk()->assertDontSee('MPCC Berikutnya');

        DB::table('users')->insert(['name' => 'MPCC Berikutnya', 'role' => 'mpcc']);
        CalendarParticipant::create(['name' => 'Peserta Tambahan', 'color' => '#123456']);

        $response = $this->get('/calendar')->assertOk()
            ->assertSee('value="MPCC Berikutnya"', false)
            ->assertSee('value="Peserta Tambahan" data-color="#123456"', false);
        $this->assertCount(16, $response->viewData('participants'));
        $this->assertTrue($response->viewData('participantGroups')['MPCC']->has('MPCC Berikutnya'));

        DB::table('users')->where('name', 'Sony Widjaya')->update(['role' => 'MPCC']);
        $groups = $this->get('/calendar')->assertOk()->viewData('participantGroups');
        $this->assertFalse($groups['PH']->has('Sony Widjaya'));
        $this->assertTrue($groups['MPCC']->has('Sony Widjaya'));
        $this->assertFalse($groups['Nama tambahan']->has('Sony Widjaya'));
    }

    public function test_event_colors_use_database_and_match_dropdown_for_new_accounts(): void
    {
        CalendarParticipant::where('name', 'Sony Widjaya')->update(['color' => '#123456']);
        foreach (['Sony Widjaya', 'PH Baru', 'MPCC Baru', 'Pemilik Jadwal Lama'] as $name) {
            Booking::create([
                'nama' => $name,
                'lokasi' => 'Kantor',
                'tanggal' => '2026-09-29',
                'waktu_mulai' => '09:00:00',
                'waktu_selesai' => '10:00:00',
                'warna' => '#654321',
            ]);
        }

        $options = $this->get('/calendar')->assertOk()->viewData('participants');
        $events = collect($this->getJson('/calendar/events')->assertOk()->json())->keyBy('extendedProps.nama');
        $this->assertSame('#123456', $events['Sony Widjaya']['backgroundColor']);
        foreach (['PH Baru', 'MPCC Baru'] as $name) {
            $this->assertSame($options[$name], $events[$name]['backgroundColor']);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $options[$name]);
        }
        $this->assertSame('#654321', $events['Pemilik Jadwal Lama']['backgroundColor']);
    }
}
