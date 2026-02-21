<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MitraSbpController extends Controller
{
    private function areaRegionalMapping(): array
    {
        return [
            'Area1' => ['SUMBAGSEL', 'SUMBAGUT', 'SUMBAGTENG'],
            'Area2' => ['JAKARTA BANTEN', 'EASTERN JABOTABEK', 'JABAR'],
            'Area3' => ['BALNUS', 'JATENG DIY', 'JATIM'],
            'Area4' => ['KALIMANTAN', 'PUMA', 'SULAWESI'],
            'HQ' => ['HQ'],
        ];
    }

    public function index(Request $request)
    {
        $items = DB::table('mitra_sbp')
            ->orderByDesc('id')
            ->get();

        return view('mitra-sbp.crud.index', compact('items'));
    }

    public function create()
    {
        $areaRegionalMap = $this->areaRegionalMapping();
        $areaOptions = array_keys($areaRegionalMap);

        return view('mitra-sbp.crud.create', compact('areaRegionalMap', 'areaOptions'));
    }

    public function store(Request $request)
    {
        $areaRegionalMap = $this->areaRegionalMapping();
        $areaOptions = array_keys($areaRegionalMap);
        $regionalOptions = collect($areaRegionalMap)->flatten()->values()->all();

        $validated = $request->validate([
            'reg_id' => ['nullable', 'string', 'max:255'],
            'email_myads' => ['required', 'email', 'max:255'],
            'area' => ['nullable', 'string', Rule::in($areaOptions)],
            'regional' => ['nullable', 'string', Rule::in($regionalOptions)],
            'remark' => ['nullable', 'string', 'max:255'],
            'voucher' => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($validated['area']) && !empty($validated['regional'])) {
            $allowedRegionalsForArea = $areaRegionalMap[$validated['area']] ?? [];
            if (!in_array($validated['regional'], $allowedRegionalsForArea, true)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['regional' => 'Regional tidak sesuai dengan Area yang dipilih.']);
            }
        }

        $validated['email_myads'] = strtolower(trim($validated['email_myads']));
        $email = $validated['email_myads'];

        $existsInMitraSbp = DB::table('mitra_sbp')
            ->whereRaw('LOWER(email_myads) = ?', [$email])
            ->exists();

        $existsInLeadsMaster = DB::table('leads_master')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        if ($existsInMitraSbp && $existsInLeadsMaster) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', 'Email sudah ada di tabel mitra_sbp dan juga terdaftar di leads_master. Silahkan hubungi tim IT.');
        }

        if ($existsInMitraSbp) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', 'Email sudah ada di tabel mitra_sbp. Silahkan hubungi tim IT.');
        }

        if ($existsInLeadsMaster) {
            return redirect()
                ->back()
                ->withInput()
                ->with('warning', 'Email sudah terdaftar di leads_master. Silahkan hubungi tim IT.');
        }

        $validated['created_at'] = now();
        $validated['updated_at'] = now();

        DB::table('mitra_sbp')->insert($validated);

        return redirect()->route('configuration.mitra-sbp.index')->with('success', 'Data Mitra SBP berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $item = DB::table('mitra_sbp')->where('id', $id)->first();

        if (!$item) {
            return redirect()->route('configuration.mitra-sbp.index')->with('error', 'Data tidak ditemukan.');
        }

        $areaRegionalMap = $this->areaRegionalMapping();
        $areaOptions = array_keys($areaRegionalMap);

        return view('mitra-sbp.crud.edit', compact('item', 'areaRegionalMap', 'areaOptions'));
    }

    public function update(Request $request, $id)
    {
        $item = DB::table('mitra_sbp')->where('id', $id)->first();
        if (!$item) {
            return redirect()->route('configuration.mitra-sbp.index')->with('error', 'Data tidak ditemukan.');
        }

        $areaRegionalMap = $this->areaRegionalMapping();
        $areaOptions = array_keys($areaRegionalMap);
        $regionalOptions = collect($areaRegionalMap)->flatten()->values()->all();

        $validated = $request->validate([
            'reg_id' => ['nullable', 'string', 'max:255'],
            'email_myads' => [
                'required',
                'email',
                'max:255',
                Rule::unique('mitra_sbp', 'email_myads')->ignore($id),
            ],
            'area' => ['nullable', 'string', Rule::in($areaOptions)],
            'regional' => ['nullable', 'string', Rule::in($regionalOptions)],
            'remark' => ['nullable', 'string', 'max:255'],
            'voucher' => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($validated['area']) && !empty($validated['regional'])) {
            $allowedRegionalsForArea = $areaRegionalMap[$validated['area']] ?? [];
            if (!in_array($validated['regional'], $allowedRegionalsForArea, true)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['regional' => 'Regional tidak sesuai dengan Area yang dipilih.']);
            }
        }

        $validated['email_myads'] = strtolower(trim($validated['email_myads']));
        $validated['updated_at'] = now();

        DB::table('mitra_sbp')->where('id', $id)->update($validated);

        return redirect()->route('configuration.mitra-sbp.index')->with('success', 'Data Mitra SBP berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $deleted = DB::table('mitra_sbp')->where('id', $id)->delete();

        if (!$deleted) {
            return redirect()->route('configuration.mitra-sbp.index')->with('error', 'Data tidak ditemukan.');
        }

        return redirect()->route('configuration.mitra-sbp.index')->with('success', 'Data Mitra SBP berhasil dihapus.');
    }
}
