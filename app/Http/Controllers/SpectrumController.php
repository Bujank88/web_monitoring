<?php

namespace App\Http\Controllers;

use App\Models\LeadSpectrum;
use App\Services\SpectrumLeadImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpectrumController extends Controller
{
    public function create(): View
    {
        return view('spectrum.leads.create');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = trim($filters['search'] ?? '');
        $leads = LeadSpectrum::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    foreach (['last_name', 'company_account', 'email', 'mobile_phone'] as $field) {
                        $query->orWhere($field, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderByDesc('id')->paginate(25)->withQueryString();

        return view('spectrum.leads.index', compact('leads', 'search'));
    }

    public function store(Request $request, SpectrumLeadImporter $importer): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']]);
        $file = $request->file('file');
        $result = $importer->import($file->getRealPath(), $file->getClientOriginalName(), $request->user()->id);

        return redirect()->route('spectrum.leads.index')->with('success', "Upload selesai: {$result['created']} leads ditambahkan, {$result['duplicates']} baris duplikat dilewati.");
    }

    public function edit(LeadSpectrum $lead): View
    {
        return view('spectrum.leads.edit', compact('lead'));
    }

    public function update(Request $request, LeadSpectrum $lead): RedirectResponse
    {
        $rules = [];
        foreach (LeadSpectrum::HEADERS as $field => $label) {
            if (in_array($field, ['create_date', 'share_date'], true)) {
                $rules[$field] = ['nullable', 'date_format:Y-m-d'];
            } elseif (in_array($field, ['description', 'fu', 'response'], true)) {
                $rules[$field] = ['nullable', 'string', function ($attribute, $value, $fail) use ($label) {
                    if (strlen($value) > 65535) {
                        $fail("Isi {$label} terlalu panjang.");
                    }
                }];
            } else {
                $rules[$field] = ['nullable', 'string', 'max:255'];
            }
        }
        $data = $request->validate($rules, [], LeadSpectrum::HEADERS);
        // Keep the original import fingerprint so reuploads do not undo manual edits.
        $lead->update($data);

        return redirect()->route('spectrum.leads.index')->with('success', 'Data lead berhasil diperbarui.');
    }

    public function destroy(LeadSpectrum $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('spectrum.leads.index')->with('success', 'Data lead berhasil dihapus.');
    }
}
