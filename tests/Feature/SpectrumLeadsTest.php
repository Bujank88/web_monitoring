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

    private function row(): array
    {
        return ['Tsel', 'Test Lead', 'Example Company', '081234567890', 'lead@example.test', '1 - 9 Karyawan', 'Digital Advertising', 'Web-to-lead', '1/2/2026', null, 'Request', 'Digital Advertising', 'Retail', 'Follow up', 'Tertarik'];
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
