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
            '2026_11_23_165456_add_user_id_to_lead_spectrums_table.php',
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

    public function test_admin_can_assign_reassign_and_clear_lead_recipient(): void
    {
        $admin = $this->createAssignmentUser('Admin', 'Admin');
        $first = $this->createAssignmentUser('First Canvasser');
        $second = $this->createAssignmentUser('Second Canvasser');
        $inactive = $this->createAssignmentUser('Inactive Canvasser', 'cvsr', 'Belum Aktif');
        $selfService = $this->createAssignmentUser('self service');
        $lead = $this->createAssignedLead();
        $uploader = $lead->uploaded_by;
        $this->actingAs($admin);
        $response = $this->get(route('spectrum.leads.index'))->assertOk();
        $response->assertSee('Canvasser Penerima')->assertSee('First Canvasser')->assertSee('Second Canvasser')
            ->assertDontSee('Inactive Canvasser')->assertDontSee('self service');
        foreach ([$first->id, $second->id, null] as $recipient) {
            $this->from(route('spectrum.leads.index'))->patch(route('spectrum.leads.assign', $lead), ['user_id' => $recipient])
                ->assertRedirect(route('spectrum.leads.index'))->assertSessionHasNoErrors();
            $this->assertSame($recipient, $lead->fresh()->user_id);
            $this->assertSame($uploader, $lead->fresh()->uploaded_by);
        }
        foreach ([$admin->id, $inactive->id, $selfService->id, 999999, 'invalid'] as $recipient) {
            $this->patch(route('spectrum.leads.assign', $lead), ['user_id' => $recipient])->assertSessionHasErrors('user_id');
            $this->assertNull($lead->fresh()->user_id);
        }
        $this->patch(route('spectrum.leads.assign', $lead), [])->assertSessionHasErrors('user_id');
    }

    public function test_admin_can_assign_on_manual_input_and_edit(): void
    {
        $admin = $this->createAssignmentUser('Admin', 'Admin');
        $canvasser = $this->createAssignmentUser('Assigned Canvasser');
        $this->actingAs($admin);
        $this->get(route('spectrum.leads.create'))->assertOk()->assertSee('Bagikan ke Canvasser');
        $this->post(route('spectrum.leads.store'), [
            '_mode' => 'manual', 'last_name' => 'Assigned Lead', 'company_account' => 'Company', 'user_id' => $canvasser->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('spectrum.leads.index'));
        $lead = LeadSpectrum::firstOrFail();
        $this->assertSame($canvasser->id, $lead->user_id);
        $this->get(route('spectrum.leads.edit', $lead))->assertOk()->assertSee('Bagikan ke Canvasser');
        $this->patch(route('spectrum.leads.update', $lead), [
            'last_name' => 'Assigned Lead', 'company_account' => 'Company', 'user_id' => null,
        ])->assertSessionHasNoErrors();
        $this->assertNull($lead->fresh()->user_id);
    }

    public function test_canvasser_can_only_see_and_update_own_assigned_leads(): void
    {
        $canvasser = $this->createAssignmentUser('Owner');
        $other = $this->createAssignmentUser('Other Owner');
        $ownLead = $this->createAssignedLead($canvasser->id);
        $otherLead = $this->createAssignedLead($other->id);
        $unassigned = $this->createAssignedLead();
        $this->actingAs($canvasser);
        $this->get(route('spectrum.leads.index', ['month' => '2026-11', 'search' => 'Company']))
            ->assertOk()->assertViewHas('leads', fn ($leads) => $leads->pluck('id')->all() === [$ownLead->id]);
        foreach ([$otherLead, $unassigned] as $lead) {
            $this->get(route('spectrum.leads.edit', $lead))->assertNotFound();
            $this->patch(route('spectrum.leads.update', $lead), ['notes' => 'Forbidden'])->assertNotFound();
            $this->assertNull($lead->fresh()->notes);
        }
        $this->get(route('spectrum.leads.edit', $ownLead))->assertOk()->assertDontSee('name="user_id"', false);
        $this->patch(route('spectrum.leads.update', $ownLead), ['notes' => 'Contacted', 'company_account' => 'Tampered'])
            ->assertSessionHasNoErrors()->assertRedirect(route('spectrum.leads.index'));
        $this->assertSame('Contacted', $ownLead->fresh()->notes);
        $this->assertSame('Company', $ownLead->fresh()->company_account);
        $this->patch(route('spectrum.leads.update', $ownLead), ['user_id' => $other->id])->assertSessionHasErrors('user_id');
        $this->patch(route('spectrum.leads.assign', $ownLead), ['user_id' => $other->id])->assertRedirect('/');
        $this->assertSame($canvasser->id, $ownLead->fresh()->user_id);
    }

    public function test_spectrum_can_manage_leads_but_cannot_change_assignment(): void
    {
        $canvasser = $this->createAssignmentUser('Owner');
        $lead = $this->createAssignedLead($canvasser->id);
        $this->get(route('spectrum.leads.index'))->assertOk()->assertSee('Owner')->assertDontSee('name="user_id"', false);
        $this->patch(route('spectrum.leads.assign', $lead), ['user_id' => null])->assertForbidden();
        $this->patch(route('spectrum.leads.update', $lead), [
            'last_name' => 'Lead', 'company_account' => 'Company', 'user_id' => 999,
        ])->assertSessionHasErrors('user_id');
        $this->patch(route('spectrum.leads.update', $lead), [
            'last_name' => 'Updated by Spectrum', 'company_account' => 'Company',
        ])->assertSessionHasNoErrors();
        $this->assertSame($canvasser->id, $lead->fresh()->user_id);
        $this->assertSame('Updated by Spectrum', $lead->fresh()->last_name);
    }

    public function test_canvasser_can_edit_exactly_the_27_progress_fields_and_initial_data_stays_unchanged(): void
    {
        $canvasser = $this->createAssignmentUser('Owner');
        $lead = $this->createAssignedLead($canvasser->id);
        $original = $lead->getAttributes();
        $progress = [
            'tgl_call_1st' => '2026-11-23', 'status_call' => 'Connected',
            'reason_call' => 'Interested', 'sub_reason_call' => 'Request info',
            'tgl_call_fu' => '2026-11-24', 'status_call_fu' => 'Connected FU',
            'reason_call_fu' => 'Interested FU', 'sub_reason_call_fu' => 'Request proposal',
            'notes' => 'Follow up tomorrow', 'region' => 'Jakarta', 'alamat' => 'Jl. Test 1',
            'cek_mea' => 'Yes', 'nama_pic' => 'PIC Name', 'nomor_pic' => '08123456789',
            'email_pic' => 'pic@example.test', 'karyawan' => '100', 'source_leads' => 'Web',
            'product_offer' => 'MyAds', 'est_cb' => '100', 'est_revenue' => '2000000', 'revenue' => '1500000',
            'detail_kebutuhan' => 'Campaign', 'agent_leads' => 'Agent', 'tgl_dispatch' => '2026-11-25',
            'corp_name_valid' => 'Valid Company', 'bulan_dispatch' => 'November 2026', 'update_status' => 'In progress',
        ];
        $this->assertCount(27, $progress);
        $this->assertSame(array_keys($progress), array_keys(LeadSpectrum::CANVASSER_FIELDS));
        $this->actingAs($canvasser);
        $response = $this->get(route('spectrum.leads.edit', $lead))->assertOk();
        foreach (array_keys($progress) as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
        $tampered = [];
        foreach (array_keys(LeadSpectrum::INITIAL_FIELDS) as $field) {
            $response->assertDontSee('name="'.$field.'"', false);
            $tampered[$field] = 'Unauthorized change';
        }
        $this->patch(route('spectrum.leads.update', $lead), $progress + $tampered + [
            'uploaded_by' => $canvasser->id, 'import_hash' => 'tampered',
        ])->assertSessionHasNoErrors()->assertRedirect(route('spectrum.leads.index'));
        $lead->refresh();
        foreach ($progress as $field => $expected) {
            $actual = $lead->$field;
            $this->assertSame($expected, $actual instanceof \DateTimeInterface ? $actual->format('Y-m-d') : $actual, $field);
        }
        foreach (array_keys(LeadSpectrum::INITIAL_FIELDS) as $field) {
            $this->assertSame($original[$field] ?? null, $lead->getRawOriginal($field), $field);
        }
        foreach (['uploaded_by', 'import_hash', 'user_id'] as $field) {
            $this->assertSame($original[$field], $lead->getRawOriginal($field), $field);
        }
    }

    public function test_spectrum_can_edit_all_initial_and_progress_fields(): void
    {
        $lead = $this->createAssignedLead();
        $response = $this->get(route('spectrum.leads.edit', $lead))->assertOk();
        $data = [];
        foreach (LeadSpectrum::HEADERS as $field => $label) {
            $response->assertSee('name="'.$field.'"', false);
            $data[$field] = match ($field) {
                'create_date', 'share_date', 'tgl_dispatch' => '2026-11-23',
                'email', 'email_pic' => $field.'@example.test',
                default => 'Updated '.$field,
            };
        }
        $this->patch(route('spectrum.leads.update', $lead), $data)
            ->assertSessionHasNoErrors()->assertRedirect(route('spectrum.leads.index'));
        $lead->refresh();
        foreach ($data as $field => $expected) {
            $actual = $lead->$field;
            $this->assertSame($expected, $actual instanceof \DateTimeInterface ? $actual->format('Y-m-d') : $actual, $field);
        }
    }

    private function createAssignmentUser(string $name, string $role = 'cvsr', string $status = 'Aktif'): User
    {
        return User::create([
            'name' => $name, 'email' => uniqid('assignment_').'@example.test',
            'role' => $role, 'status' => $status, 'password' => 'password',
        ]);
    }

    private function createAssignedLead(?int $recipient = null): LeadSpectrum
    {
        return LeadSpectrum::create([
            'last_name' => 'Lead', 'company_account' => 'Company', 'user_id' => $recipient,
            'create_date' => '2026-11-23', 'uploaded_by' => auth()->id(),
            'import_hash' => hash('sha256', uniqid('assignment_', true)), 'import_filename' => 'test.xlsx',
        ]);
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
