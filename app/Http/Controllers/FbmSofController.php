<?php

namespace App\Http\Controllers;

use App\Models\FbmSof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FbmSofController extends Controller
{
    public function create()
    {
        logUserLogin();

        return view('fbm.sof_create', [
            'pageTitle' => 'Pengajuan SOF',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sender_name' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:50',
            'verif_bisnis' => 'required|in:Yes,On Progress,No',
        ], [
            'sender_name.required' => 'Sender Name wajib diisi.',
            'nomor_wa.required' => 'Nomor WA wajib diisi.',
            'verif_bisnis.required' => 'Verif Bisnis wajib dipilih.',
        ]);

        FbmSof::create([
            'sender_name' => trim($validated['sender_name']),
            'nomor_wa' => trim($validated['nomor_wa']),
            'pic' => (string) optional(Auth::user())->name,
            'verif_bisnis' => $validated['verif_bisnis'],
            'credit_line' => 'No',
            'sof_uploaded_at' => null,
        ]);

        return redirect()
            ->route('fbm.sof.create')
            ->with('success', 'Pengajuan SOF berhasil disimpan.');
    }

    public function index()
    {
        logUserLogin();

        $roleUpper = strtoupper(trim((string) optional(Auth::user())->role));
        $isAdmin = $roleUpper === 'ADMIN';

        return view('fbm.sof_index', [
            'pageTitle' => 'List SOF',
            'isAdmin' => $isAdmin,
        ]);
    }

    public function data()
    {
        $roleUpper = strtoupper(trim((string) optional(Auth::user())->role));
        $isAdmin = $roleUpper === 'ADMIN';

        $query = FbmSof::query()->select([
            'id',
            'sender_name',
            'nomor_wa',
            'pic',
            'verif_bisnis',
            'credit_line',
            'sof_file',
            'sof_uploaded_at',
            'created_at',
        ])
            ->orderByRaw("
                CASE
                    WHEN verif_bisnis = 'No' AND credit_line = 'No' THEN 0
                    WHEN verif_bisnis = 'No' THEN 1
                    WHEN credit_line = 'No' THEN 2
                    WHEN verif_bisnis = 'On Progress' OR credit_line = 'On Progress' THEN 3
                    ELSE 4
                END ASC
            ")
            ->orderByDesc('id');

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('sof_file_label', function ($row) use ($isAdmin) {
                if (!$isAdmin) {
                    return '-';
                }

                if (!$row->sof_file) {
                    return '-';
                }

                return '<a href="' . route('fbm.sof.download', $row->id) . '" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-download mr-1"></i> Download SOF
                        </a>';
            })
            ->addColumn('action', function ($row) {
                return '<a href="' . route('fbm.sof.edit', $row->id) . '" class="btn btn-sm btn-primary">Edit</a>';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at->format('d-m-Y H:i') : '-';
            })
            ->editColumn('sof_uploaded_at', function ($row) {
                return $row->sof_uploaded_at ? $row->sof_uploaded_at->format('d-m-Y H:i') : '-';
            })
            ->rawColumns(['sof_file_label', 'action'])
            ->make(true);
    }

    public function edit(FbmSof $sof)
    {
        logUserLogin();

        $roleUpper = strtoupper(trim((string) optional(Auth::user())->role));
        $isAdmin = $roleUpper === 'ADMIN';

        return view('fbm.sof_edit', [
            'pageTitle' => 'Edit SOF',
            'sof' => $sof,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function update(Request $request, FbmSof $sof)
    {
        $roleUpper = strtoupper(trim((string) optional(Auth::user())->role));
        $isAdmin = $roleUpper === 'ADMIN';

        $rules = [
            'verif_bisnis' => 'required|in:Yes,On Progress,No',
        ];

        if ($isAdmin) {
            $rules['credit_line'] = 'required|in:Yes,On Progress,No';
            $rules['sof_file'] = 'nullable|file|mimes:pdf|max:5120';
        }

        $validated = $request->validate($rules, [
            'verif_bisnis.required' => 'Verif Bisnis wajib dipilih.',
            'credit_line.required' => 'Credit Line wajib dipilih.',
            'sof_file.mimes' => 'File SOF harus berformat PDF.',
            'sof_file.max' => 'Ukuran file SOF maksimal 5 MB.',
        ]);

        $payload = [
            'verif_bisnis' => $validated['verif_bisnis'],
        ];

        if ($isAdmin) {
            $payload['credit_line'] = $validated['credit_line'];

            if ($request->hasFile('sof_file')) {
                $directory = public_path('uploads/fbm_sof');
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }

                $file = $request->file('sof_file');
                $filename = 'sof_' . $sof->id . '_' . Str::slug($sof->sender_name) . '_' . time() . '.pdf';
                $file->move($directory, $filename);
                $payload['sof_file'] = 'uploads/fbm_sof/' . $filename;
                $payload['sof_uploaded_at'] = now();
            }
        }

        $sof->update($payload);

        return redirect()
            ->route('fbm.sof.index')
            ->with('success', 'Data SOF berhasil diperbarui.');
    }

    public function download(FbmSof $sof)
    {
        $roleUpper = strtoupper(trim((string) optional(Auth::user())->role));
        if ($roleUpper !== 'ADMIN') {
            abort(403);
        }

        if (!$sof->sof_file) {
            abort(404);
        }

        $filePath = public_path($sof->sof_file);
        if (!File::exists($filePath)) {
            abort(404);
        }

        $downloadName = 'SOF_' . Str::slug($sof->sender_name ?: 'fbm') . '.pdf';

        return response()->download($filePath, $downloadName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
