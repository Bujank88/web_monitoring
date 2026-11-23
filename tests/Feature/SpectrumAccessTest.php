<?php

namespace Tests\Feature;

use App\Models\LeadSpectrum;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SpectrumAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'nohp', 'role', 'status', 'area', 'branch', 'regional', 'referral_code', 'remember_token'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('loglogin', function (Blueprint $table) {
            $table->integer('user_id');
            foreach (['tgl', 'nama', 'role', 'email'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamps();
        });
        foreach ([
            '2026_10_06_000001_create_leads_spectrum_table.php',
            '2026_10_27_000001_add_utm_source_to_leads_spectrum_table.php',
            '2026_11_23_145339_add_extended_columns_to_leads_spectrum_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->actingAs(User::create([
            'name' => 'Spectrum', 'email' => 'spectrum@example.test', 'password' => 'spectrum-password',
            'role' => 'spectrum', 'status' => 'Aktif',
        ]));
    }

    public function test_spectrum_sees_only_its_menus_and_can_manage_leads(): void
    {
        $this->get('/')->assertRedirect(route('spectrum.leads.index'));
        $this->get('/login')->assertRedirect(route('spectrum.leads.index'));
        $response = $this->get(route('spectrum.leads.index'))->assertOk();
        foreach (['spectrum.leads.index', 'spectrum.leads.create', 'change-password', 'logout'] as $name) {
            $response->assertSee('href="'.route($name).'"', false);
        }
        foreach (['admin.home', 'users.page', 'daily.topup.channel', 'presensi.index', 'leads-master.index'] as $name) {
            $response->assertDontSee('href="'.route($name).'"', false);
        }
        $this->get(route('spectrum.leads.create'))->assertOk();
        $this->post(route('spectrum.leads.store'), [
            '_mode' => 'manual', 'last_name' => 'New Lead', 'company_account' => 'Company',
        ])->assertRedirect(route('spectrum.leads.index'))->assertSessionHasNoErrors();
        $lead = LeadSpectrum::firstOrFail();
        $this->get(route('spectrum.leads.index'))->assertOk()
            ->assertSee(route('spectrum.leads.edit', $lead))->assertSee('Delete');
        $this->get(route('spectrum.leads.edit', $lead))->assertOk();
        $this->patch(route('spectrum.leads.update', $lead), [
            'last_name' => 'Updated Lead', 'company_account' => 'Company', 'notes' => 'Contacted',
        ])->assertRedirect(route('spectrum.leads.index'))->assertSessionHasNoErrors();
        $this->assertSame('Updated Lead', $lead->fresh()->last_name);
        $this->assertSame('Contacted', $lead->fresh()->notes);
        $this->delete(route('spectrum.leads.destroy', $lead))->assertRedirect(route('spectrum.leads.index'));
        $this->assertDatabaseMissing('leads_spectrum', ['id' => $lead->id]);
    }

    public function test_spectrum_can_upload_excel(): void
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->fromArray(array_values(LeadSpectrum::HEADERS), null, 'A1');
        $sheet->setCellValue('B2', 'Uploaded Lead');
        $sheet->setCellValue('C2', 'Uploaded Company');
        $path = tempnam(sys_get_temp_dir(), 'spectrum_access_');
        try {
            (new Xlsx($book))->save($path);
            $this->post(route('spectrum.leads.store'), [
                'file' => new UploadedFile($path, 'leads.xlsx', null, null, true),
            ])->assertRedirect(route('spectrum.leads.index'))->assertSessionHasNoErrors();
            $this->assertDatabaseHas('leads_spectrum', ['last_name' => 'Uploaded Lead', 'uploaded_by' => auth()->id()]);
        } finally {
            $book->disconnectWorksheets();
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_spectrum_cannot_open_other_menus_or_mutate_other_data(): void
    {
        foreach (['admin.home', 'users.page', 'users.data', 'daily.topup.channel', 'leads-master.index', 'tips-sales'] as $name) {
            $this->get(route($name))->assertForbidden();
        }
        $this->get('/get-regionals')->assertForbidden();
        $this->get('/summary-padi-umkm')->assertForbidden();
        $this->get('/register')->assertForbidden();
        $this->post(route('users.store'), [])->assertForbidden();
        $this->post(route('users.update', 1), ['role' => 'Admin'])->assertForbidden();
        $this->post(route('register.store'), [])->assertForbidden();
        $this->post(route('panenpoinv4.store'), [])->assertForbidden();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('spectrum', User::first()->role);
    }

    public function test_spectrum_login_password_change_and_logout_work(): void
    {
        auth()->logout();
        $this->post(route('login'), ['email' => 'spectrum@example.test', 'password' => 'spectrum-password'])
            ->assertRedirect(route('spectrum.leads.index'));
        $this->get(route('change-password'))->assertOk();
        $this->post(route('password.update'), [
            'current_password' => 'spectrum-password', 'current_password_confirmation' => 'spectrum-password',
            'new_password' => 'new-spectrum-password', 'new_password_confirmation' => 'new-spectrum-password',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-spectrum-password', User::first()->password));
        $this->assertGuest();
        $this->actingAs(User::first())->get(route('logout'))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_admin_can_create_and_assign_spectrum_role(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'admin-password',
            'role' => 'Admin', 'status' => 'Aktif',
        ]);
        $this->actingAs($admin);
        $this->get(route('users.page'))->assertOk()->assertSee('<option value="spectrum">Spectrum</option>', false);
        $data = ['name' => 'New Spectrum', 'email' => 'new-spectrum@example.test', 'nohp' => '08123456789', 'role' => 'spectrum'];
        $this->post(route('users.store'), $data)->assertOk()->assertSessionHasNoErrors();
        $account = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame('spectrum', $account->role);
        $this->post(route('users.update', $account->id), array_merge($data, ['name' => 'Updated Spectrum']))
            ->assertOk()->assertSessionHasNoErrors();
        $this->assertSame('Updated Spectrum', $account->fresh()->name);
        $this->assertSame('spectrum', $account->fresh()->role);
    }
}
