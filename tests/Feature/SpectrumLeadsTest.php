<?php

namespace Tests\Feature;

use App\Models\LeadSpectrum;
use App\Models\User;
use App\Services\SpectrumLeadImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SpectrumLeadsTest extends TestCase
{
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'email', 'password', 'role'] as $field) {
                $table->string($field);
            }
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_06_000001_create_leads_spectrum_table.php'))->up();
        (require database_path('migrations/2026_10_27_000001_add_utm_source_to_leads_spectrum_table.php'))->up();
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

    public function test_only_admin_can_access_and_upload_leads(): void
    {
        $this->get(route('spectrum.leads.create'))->assertRedirect('/login');
        $this->get(route('spectrum.leads.index'))->assertRedirect('/login');
        $this->post(route('spectrum.leads.store'))->assertRedirect('/login');
        foreach (['cvsr', 'Tsel', 'PH'] as $role) {
            $this->actingAs($this->user($role));
            $this->get(route('spectrum.leads.create'))->assertRedirect('/');
            $this->get(route('spectrum.leads.index'))->assertRedirect('/');
            $this->post(route('spectrum.leads.store'))->assertRedirect('/');
        }
        $this->actingAs($this->user('Admin'));
        $this->get(route('spectrum.leads.create'))->assertOk()->assertSee('Upload Leads');
        $this->get(route('spectrum.leads.index'))->assertOk()->assertSee('Belum ada leads');
    }

    public function test_upload_maps_all_headers_preserves_phones_and_excel_dates_and_lists_results(): void
    {
        $admin = $this->user('Admin');
        $row = $this->row();
        $row[8] = Date::PHPToExcel(new \DateTimeImmutable('2026-01-02'));
        $row[9] = '2026-01-05';
        $file = $this->workbook([$row], true);
        $this->actingAs($admin)->post(route('spectrum.leads.store'), ['file' => new UploadedFile($file, 'leads.xlsx', null, null, true)])
            ->assertRedirect(route('spectrum.leads.index'))->assertSessionHas('success');
        $lead = LeadSpectrum::firstOrFail();
        $this->assertSame('081234567890', $lead->mobile_phone);
        $this->assertSame('2026-01-02', $lead->create_date->format('Y-m-d'));
        $this->assertSame('2026-01-05', $lead->share_date->format('Y-m-d'));
        $this->assertSame('Follow up', $lead->fu);
        $this->assertSame('Tertarik', $lead->response);
        $this->assertSame($admin->id, $lead->uploaded_by);
        $this->get(route('spectrum.leads.index'))->assertOk()->assertSee('Example Company')->assertSee('081234567890')->assertSee('02/01/2026');
    }

    public function test_reupload_skips_identical_rows_but_allows_different_leads_with_same_email(): void
    {
        $file = $this->workbook([$this->row(), $this->row()]);
        $importer = app(SpectrumLeadImporter::class);
        $this->assertSame(['created' => 1, 'duplicates' => 1], $importer->import($file, 'leads.xlsx', 1));
        $this->assertSame(['created' => 0, 'duplicates' => 2], $importer->import($file, 'leads.xlsx', 1));
        $changed = $this->row();
        $changed[10] = 'Another request';
        $this->assertSame(['created' => 1, 'duplicates' => 0], $importer->import($this->workbook([$changed]), 'new.xlsx', 1));
        $this->assertSame(2, LeadSpectrum::count());
    }

    public function test_invalid_later_date_does_not_save_earlier_valid_rows(): void
    {
        $invalid = $this->row();
        $invalid[8] = '2026-02-30';
        $file = $this->workbook([$this->row(), $invalid]);
        $this->actingAs($this->user('Admin'))->from(route('spectrum.leads.create'))
            ->post(route('spectrum.leads.store'), ['file' => new UploadedFile($file, 'leads.xlsx', null, null, true)])
            ->assertRedirect(route('spectrum.leads.create'))->assertSessionHasErrors('file');
        $this->assertSame(0, LeadSpectrum::count());
    }

    public function test_missing_headers_and_formula_cells_are_rejected(): void
    {
        $admin = $this->user('Admin');
        $missing = $this->workbook([$this->row()], false, ['Provider']);
        $formula = $this->workbook([$this->row()]);
        $book = IOFactory::load($formula);
        $book->getActiveSheet()->setCellValue('K2', '=1+1');
        (new Xlsx($book))->save($formula);
        $book->disconnectWorksheets();
        foreach ([$missing, $formula] as $file) {
            $this->actingAs($admin)->post(route('spectrum.leads.store'), ['file' => new UploadedFile($file, 'leads.xlsx', null, null, true)])
                ->assertSessionHasErrors('file');
        }
        $this->assertSame(0, LeadSpectrum::count());
    }

    public function test_blank_rows_are_skipped_and_search_and_pagination_work(): void
    {
        $rows = [$this->row(), array_fill(0, 15, null)];
        for ($index = 1; $index <= 26; $index++) {
            $row = $this->row();
            $row[1] = 'Other '.$index;
            $row[2] = 'Other Company';
            $row[4] = "other{$index}@example.test";
            $rows[] = $row;
        }
        $result = app(SpectrumLeadImporter::class)->import($this->workbook($rows), 'leads.xlsx', 1);
        $this->assertSame(27, $result['created']);
        $this->actingAs($this->user('Admin'))->get(route('spectrum.leads.index', ['search' => 'Example Company']))
            ->assertOk()->assertSee('Example Company')->assertDontSee('Other Company');
        $this->get(route('spectrum.leads.index'))->assertOk()->assertSee('page=2', false);
        $this->get(route('spectrum.leads.index', ['page' => 2]))->assertOk()->assertSee('Example Company');
    }

    public function test_admin_can_edit_all_fields_and_reupload_preserves_manual_edits(): void
    {
        $file = $this->workbook([$this->row()]);
        app(SpectrumLeadImporter::class)->import($file, 'leads.xlsx', 1);
        $lead = LeadSpectrum::firstOrFail();
        $hash = $lead->import_hash;
        $data = array_combine(array_keys(LeadSpectrum::HEADERS), $this->row());
        $data['last_name'] = 'Updated Lead';
        $data['mobile_phone'] = '00123456789';
        $data['create_date'] = '2026-10-27';
        $data['share_date'] = null;
        $data['fu'] = 'Hubungi kembali';
        $data['utm_source'] = 'newsletter';
        $this->actingAs($this->user('Admin'));
        $this->get(route('spectrum.leads.index'))->assertOk()
            ->assertSee(route('spectrum.leads.edit', $lead))->assertSee('Delete');
        $this->get(route('spectrum.leads.edit', $lead))->assertOk()->assertSee('Example Company');
        $this->patch(route('spectrum.leads.update', $lead), $data + ['import_hash' => 'tampered', 'uploaded_by' => 999])
            ->assertRedirect(route('spectrum.leads.index'))->assertSessionHasNoErrors();
        $lead->refresh();
        foreach ($data as $field => $value) {
            $actual = $lead->$field;
            if ($actual instanceof \DateTimeInterface) {
                $actual = $actual->format('Y-m-d');
            }
            $this->assertSame($value, $actual, $field);
        }
        $this->assertSame($hash, $lead->import_hash);
        $this->assertSame(1, (int) $lead->uploaded_by);
        $this->assertSame(['created' => 0, 'duplicates' => 1], app(SpectrumLeadImporter::class)->import($file, 'leads.xlsx', 1));
        $this->assertSame('Updated Lead', $lead->fresh()->last_name);
    }

    public function test_invalid_edit_does_not_change_data_and_admin_can_delete(): void
    {
        app(SpectrumLeadImporter::class)->import($this->workbook([$this->row()]), 'leads.xlsx', 1);
        $lead = LeadSpectrum::firstOrFail();
        $this->actingAs($this->user('Admin'));
        $this->from(route('spectrum.leads.edit', $lead))->patch(route('spectrum.leads.update', $lead), [
            'last_name' => str_repeat('a', 256),
            'create_date' => '2026-02-30',
            'description' => str_repeat('a', 65536),
            'utm_source' => str_repeat('a', 256),
        ])->assertSessionHasErrors(['last_name', 'create_date', 'description', 'utm_source']);
        $this->assertSame('Test Lead', $lead->fresh()->last_name);
        $this->delete(route('spectrum.leads.destroy', $lead))
            ->assertRedirect(route('spectrum.leads.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('leads_spectrum', ['id' => $lead->id]);
        $this->get(route('spectrum.leads.edit', $lead))->assertNotFound();
        $this->patch(route('spectrum.leads.update', $lead), [])->assertNotFound();
        $this->delete(route('spectrum.leads.destroy', $lead))->assertNotFound();
    }

    public function test_guests_and_non_admins_cannot_edit_or_delete(): void
    {
        app(SpectrumLeadImporter::class)->import($this->workbook([$this->row()]), 'leads.xlsx', 1);
        $lead = LeadSpectrum::firstOrFail();
        $this->get(route('spectrum.leads.edit', $lead))->assertRedirect('/login');
        $this->patch(route('spectrum.leads.update', $lead), [])->assertRedirect('/login');
        $this->delete(route('spectrum.leads.destroy', $lead))->assertRedirect('/login');
        foreach (['cvsr', 'Tsel', 'PH'] as $role) {
            $this->actingAs($this->user($role));
            $this->get(route('spectrum.leads.edit', $lead))->assertRedirect('/');
            $this->patch(route('spectrum.leads.update', $lead), ['last_name' => 'Forbidden'])->assertRedirect('/');
            $this->delete(route('spectrum.leads.destroy', $lead))->assertRedirect('/');
        }
        $this->assertSame('Test Lead', $lead->fresh()->last_name);
    }

    public function test_optional_utm_source_import_and_legacy_fingerprints(): void
    {
        $importer = app(SpectrumLeadImporter::class);
        $legacyHeaders = array_values(array_diff_key(LeadSpectrum::HEADERS, ['utm_source' => true]));
        $legacyRow = array_slice($this->row(), 0, 15);
        $legacyFile = $this->workbook([$legacyRow], false, $legacyHeaders);
        $this->assertSame(['created' => 1, 'duplicates' => 0], $importer->import($legacyFile, 'old.xlsx', 1));
        $lead = LeadSpectrum::firstOrFail();
        $this->assertNull($lead->utm_source);
        $legacyData = array_combine(array_keys(array_diff_key(LeadSpectrum::HEADERS, ['utm_source' => true])), $legacyRow);
        $legacyData['create_date'] = '2026-01-02';
        $this->assertSame(hash('sha256', json_encode($legacyData, JSON_THROW_ON_ERROR)), $lead->import_hash);
        $this->assertSame(['created' => 0, 'duplicates' => 1], $importer->import($this->workbook([$this->row()]), 'blank-utm.xlsx', 1));

        $row = $this->row();
        $row[15] = 'google';
        $utmFile = $this->workbook([$row], true);
        $this->assertSame(['created' => 1, 'duplicates' => 0], $importer->import($utmFile, 'utm.xlsx', 1));
        $this->assertSame(['created' => 0, 'duplicates' => 1], $importer->import($utmFile, 'utm.xlsx', 1));
        $this->actingAs($this->user('Admin'))->get(route('spectrum.leads.index'))
            ->assertOk()->assertSee('UTM Source')->assertSee('google');
        $row[15] = str_repeat('a', 256);
        $this->post(route('spectrum.leads.store'), [
            'file' => new UploadedFile($this->workbook([$row]), 'long-utm.xlsx', null, null, true),
        ])->assertSessionHasErrors('file');
        $this->assertSame(2, LeadSpectrum::count());
    }

    private function row(): array
    {
        return ['Tsel', 'Test Lead', 'Example Company', '081234567890', 'lead@example.test', '1 - 9 Karyawan', 'Digital Advertising', 'Web-to-lead', '1/2/2026', null, 'Request', 'Digital Advertising', 'Retail', 'Follow up', 'Tertarik', null];
    }

    private function user(string $role): User
    {
        return User::create(['name' => $role, 'email' => $role.'@example.test', 'password' => 'password', 'role' => $role]);
    }

    private function workbook(array $rows, bool $reverse = false, ?array $headers = null): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $headers ??= array_values(LeadSpectrum::HEADERS);
        $sheet->fromArray($reverse ? array_reverse($headers) : $headers, null, 'A1');
        foreach ($rows as $index => $row) {
            foreach (($reverse ? array_reverse($row) : $row) as $column => $value) {
                if ($value !== null) {
                    $sheet->setCellValueExplicit([$column + 1, $index + 2], $value, is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                }
            }
        }
        $base = tempnam(sys_get_temp_dir(), 'spectrum_');
        $this->files[] = $base;
        $this->files[] = $file = $base.'.xlsx';
        (new Xlsx($book))->save($file);
        $book->disconnectWorksheets();

        return $file;
    }
}
