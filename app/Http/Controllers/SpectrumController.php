<?php

namespace App\Http\Controllers;

use App\Models\LeadSpectrum;
use App\Models\User;
use App\Services\SpectrumLeadImporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;

class SpectrumController extends Controller
{
    public function create(): View
    {
        return view('spectrum.leads.create', ['canvassers' => $this->assignableCanvassers()]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $search = trim($filters['search'] ?? '');
        $month = $filters['month'] ?? '';
        $leads = LeadSpectrum::query()
            ->with('canvasser:id,name')
            ->when($request->user()->hasRole('cvsr'), fn ($query) => $query->where('user_id', $request->user()->id))
            ->when($month !== '', function ($query) use ($month) {
                $start = Carbon::createFromFormat('!Y-m', $month);
                $query->where('create_date', '>=', $start->format('Y-m-d'))
                    ->where('create_date', '<', $start->copy()->addMonth()->format('Y-m-d'));
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    foreach (['last_name', 'company_account', 'email', 'mobile_phone'] as $field) {
                        $query->orWhere($field, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderByDesc('id')->paginate(25)->withQueryString();

        return view('spectrum.leads.index', [
            'leads' => $leads, 'search' => $search, 'month' => $month,
            'canvassers' => $this->assignableCanvassers(),
        ]);
    }

    public function store(Request $request, SpectrumLeadImporter $importer): RedirectResponse
    {
        if ($request->input('_mode') === 'manual') {
            $validated = $this->validateLead($request);

            $validated['import_hash'] = hash(
                'sha256',
                uniqid('manual_', true) . '|' . ($validated['mobile_phone'] ?? '') . '|' . ($validated['email'] ?? '')
            );
            $validated['import_filename'] = 'Manual Input';
            $validated['uploaded_by'] = Auth::id();

            if (empty($validated['bulan_dispatch']) && !empty($validated['tgl_dispatch'])) {
                $validated['bulan_dispatch'] = Carbon::parse($validated['tgl_dispatch'])->translatedFormat('F Y');
            }

            LeadSpectrum::create($validated);

            return redirect()->route('spectrum.leads.index')
                ->with('success', 'Data lead berhasil ditambahkan.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $file = $request->file('file');
        $result = $importer->import($file->getRealPath(), $file->getClientOriginalName(), (int) Auth::id());

        return redirect()->route('spectrum.leads.index')
            ->with('success', "Import selesai: {$result['created']} leads ditambahkan, {$result['duplicates']} duplikat dilewati.");
    }

    public function edit(LeadSpectrum $lead): View
    {
        $this->authorizeLead($lead);

        return view('spectrum.leads.edit', [
            'lead' => $lead, 'canvassers' => $this->assignableCanvassers(),
        ]);
    }

    public function update(Request $request, LeadSpectrum $lead): RedirectResponse
    {
        $this->authorizeLead($lead);
        $isCanvasser = strtolower(trim((string) Auth::user()->role)) === 'cvsr';
        $validated = $isCanvasser
            ? $this->validateCanvasserFields($request)
            : $this->validateLead($request);

        if (empty($validated['bulan_dispatch']) && !empty($validated['tgl_dispatch'])) {
            $validated['bulan_dispatch'] = Carbon::parse($validated['tgl_dispatch'])->translatedFormat('F Y');
        }

        $lead->update($validated);

        return redirect()->route('spectrum.leads.index')
            ->with('success', 'Progress lead berhasil diperbarui.');
    }

    public function destroy(LeadSpectrum $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('spectrum.leads.index')
            ->with('success', 'Data lead berhasil dihapus.');
    }

    public function assign(Request $request, LeadSpectrum $lead): RedirectResponse
    {
        abort_unless($request->user()->hasRole('Admin'), 403);
        $data = $request->validate(['user_id' => ['present', ...$this->assignmentRules()]]);
        $lead->update($data);

        return back()->with('success', 'Pembagian lead berhasil diperbarui.');
    }

    private function authorizeLead(LeadSpectrum $lead): void
    {
        if (Auth::user()->hasRole('cvsr')) {
            abort_unless((int) $lead->user_id === (int) Auth::id(), 404);
        }
    }

    private function assignableCanvassers(): Collection
    {
        if (! Auth::user()->hasRole('Admin')) {
            return new Collection;
        }

        return User::where('role', 'cvsr')->where('status', 'Aktif')
            ->where('name', '!=', 'self service')->orderBy('name')->get(['id', 'name', 'email']);
    }

    private function assignmentRules(): array
    {
        return [
            'nullable', 'integer',
            Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('role', 'cvsr')->where('status', 'Aktif')->where('name', '!=', 'self service')),
        ];
    }

    private function validateLead(Request $request): array
    {
        return $request->validate($this->leadRules() + [
            'user_id' => $request->user()->hasRole('Admin') ? $this->assignmentRules() : ['prohibited'],
        ]);
    }

    private function validateCanvasserFields(Request $request): array
    {
        return $request->validate(array_intersect_key($this->leadRules(), LeadSpectrum::CANVASSER_FIELDS) + [
            'user_id' => ['prohibited'],
        ]);
    }

    private function leadRules(): array
    {
        return [
            // Blok 1: Data Awal Leads
            'provider' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_account' => 'required|string|max:255',
            'mobile_phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'company_size' => 'nullable|string|max:255',
            'product' => 'nullable|string|max:255',
            'lead_source' => 'nullable|string|max:255',
            'create_date' => 'nullable|date',
            'share_date' => 'nullable|date',
            'description' => 'nullable|string',
            'pillar' => 'nullable|string|max:255',
            'industry_sector' => 'nullable|string|max:255',
            'fu' => 'nullable|string',
            'response' => 'nullable|string',
            'utm_source' => 'nullable|string|max:255',

            // Blok 2: Call 1st
            'tgl_call_1st' => 'nullable|string|max:255',
            'status_call' => 'nullable|string|max:255',
            'reason_call' => 'nullable|string|max:255',
            'sub_reason_call' => 'nullable|string|max:255',

            // Blok 3: Call FU
            'tgl_call_fu' => 'nullable|string|max:255',
            'status_call_fu' => 'nullable|string|max:255',
            'reason_call_fu' => 'nullable|string|max:255',
            'sub_reason_call_fu' => 'nullable|string|max:255',

            // Blok 4: Notes
            'notes' => 'nullable|string',

            // Blok 5: Kualifikasi PIC, Penawaran & Dispatch
            'region' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'cek_mea' => 'nullable|string|max:255',
            'nama_pic' => 'nullable|string|max:255',
            'nomor_pic' => 'nullable|string|max:255',
            'email_pic' => 'nullable|email|max:255',
            'karyawan' => 'nullable|string|max:255',
            'source_leads' => 'nullable|string|max:255',
            'product_offer' => 'nullable|string|max:255',
            'est_cb' => 'nullable|string|max:255',
            'est_revenue' => 'nullable|string|max:255',
            'revenue' => 'nullable|string|max:255',
            'detail_kebutuhan' => 'nullable|string',
            'agent_leads' => 'nullable|string|max:255',
            'tgl_dispatch' => 'nullable|date',
            'corp_name_valid' => 'nullable|string|max:255',
            'bulan_dispatch' => 'nullable|string|max:255',
            'update_status' => 'nullable|string|max:255',
        ];
    }
}
