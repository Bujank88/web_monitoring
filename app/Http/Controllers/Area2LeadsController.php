<?php

namespace App\Http\Controllers;

use App\Models\LeadsMaster;
use App\Models\LeadsSource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Area2LeadsController extends Controller
{
    private const REGIONALS = ['JABODETABEK', 'JABAR', 'DKI Jakarta', 'BANDUNG'];

    public function index()
    {
        logUserLogin();

        return view('area2.leads-master-index', [
            'canvassers' => Cache::remember('users_list_leads_area2', 3600, fn() =>
                DB::table('leads_master as lm')
                    ->join('users as u', 'u.id', '=', 'lm.user_id')
                    ->whereIn('lm.regional', self::REGIONALS)
                    ->where('u.role', 'MPCC')
                    ->select('u.id', 'u.name', 'u.role')
                    ->distinct()
                    ->orderBy('u.name')
                    ->get()
            ),
            'sources'    => Cache::remember('sources_list_leads', 3600, fn() => LeadsSource::orderBy('name')->get()),
            'regionals'  => collect(self::REGIONALS),
            'flagEvents' => Cache::remember('flag_events_list_leads_area2', 3600, fn() =>
                DB::table('detail_leads_summary')
                    ->whereIn('regional', self::REGIONALS)
                    ->whereNotNull('flag_event')
                    ->where('flag_event', '!=', '')
                    ->distinct()
                    ->orderBy('flag_event')
                    ->pluck('flag_event')
            ),
            'dataTypes' => Cache::remember('data_types_list_leads_area2', 3600, fn() =>
                DB::table('leads_master')
                    ->whereIn('regional', self::REGIONALS)
                    ->whereNotNull('data_type')
                    ->where('data_type', '!=', '')
                    ->distinct()
                    ->orderBy('data_type')
                    ->pluck('data_type')
            ),
        ]);
    }

    public function data(Request $request)
    {
        $search = $request->input('search.value');

        $query = DB::table('leads_master as lm')
            ->leftJoin('users as u', 'u.id', '=', 'lm.user_id')
            ->leftJoin('detail_leads_summary as dls', 'dls.leads_master_id', '=', 'lm.id')
            ->selectRaw('
                lm.id as leads_master_id,
                lm.user_id,
                COALESCE(dls.user_name, u.name) as user_name,
                lm.regional,
                lm.company_name,
                lm.email,
                lm.mobile_phone,
                lm.data_type,
                dls.flag_event,
                lm.created_at,
                COALESCE(dls.total_settlement_klien, 0) as total_settlement_klien,
                COALESCE(dls.saldo_utama, 0) as saldo_utama
            ')
            ->whereIn('lm.regional', self::REGIONALS)
            ->whereNotNull('lm.user_id')
            ->whereNotNull('u.id')
            ->where('u.role', 'MPCC')
            ->orderByRaw('COALESCE(dls.total_settlement_klien, 0) desc')
            ->orderByRaw('COALESCE(dls.saldo_utama, 0) desc');

        if ($request->regional) {
            $query->where('lm.regional', $request->regional);
        }

        if ($request->canvasser) {
            $query->where('lm.user_id', $request->canvasser);
        }

        if ($request->flag_event) {
            $query->where('dls.flag_event', $request->flag_event);
        }

        if ($request->data_type) {
            $query->where('lm.data_type', $request->data_type);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('lm.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%$search%")
                    ->orWhere('lm.regional', 'like', "%$search%")
                    ->orWhere('lm.company_name', 'like', "%$search%")
                    ->orWhere('lm.email', 'like', "%$search%")
                    ->orWhere('lm.mobile_phone', 'like', "%$search%")
                    ->orWhere('dls.flag_event', 'like', "%$search%");
            });
        }

        $rows = $query->get()->map(function ($row) {
            $type = $row->data_type ?? '-';
            if ($type === 'Eksisting Akun') {
                $typeBadge = '<span class="badge badge-success">' . $type . '</span>';
            } elseif ($type === 'Leads') {
                $typeBadge = '<span class="badge badge-primary">' . $type . '</span>';
            } else {
                $typeBadge = '<span class="badge badge-secondary">' . $type . '</span>';
            }

            $btn = '
                <a href="' . route('area2.leads-master.show', $row->leads_master_id) . '" class="btn btn-sm btn-warning mt-1">
                    <i class="fas fa-search"></i> Lihat
                </a>
            ';

            return [
                'user_name' => $row->user_name ?? '-',
                'regional' => $row->regional ?? '-',
                'company_name' => $row->company_name ?? '-',
                'email' => $row->email ?? '-',
                'mobile_phone' => $row->mobile_phone ?? '-',
                'data_type' => $typeBadge,
                'flag_event' => $row->flag_event ?? '-',
                'created_at' => $row->created_at ? \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y') : '-',
                'total_settlement_klien' => 'Rp ' . number_format($row->total_settlement_klien ?? 0, 0, ',', '.'),
                'saldo_utama' => 'Rp ' . number_format($row->saldo_utama ?? 0, 0, ',', '.'),
                'rekomendasi' => ($row->saldo_utama ?? 0) >= 1000000
                    ? '<span class="badge badge-warning">Push Campaign</span>'
                    : '<span class="badge badge-danger">Push Topup</span>',
                'aksi' => $btn,
            ];
        });

        return datatables()->of($rows)
            ->rawColumns(['aksi', 'data_type', 'rekomendasi'])
            ->make(true);
    }

    public function show($id)
    {
        logUserLogin();

        $lead = LeadsMaster::with(['user', 'source', 'sector'])
            ->whereIn('regional', self::REGIONALS)
            ->whereHas('user', function ($query) {
                $query->where('role', 'MPCC');
            })
            ->findOrFail($id);

        return view('area2.leads-master-show', compact('lead'));
    }

    public function export(Request $request)
    {
        $query = DB::table('leads_master as lm')
            ->leftJoin('users as u', 'u.id', '=', 'lm.user_id')
            ->leftJoin('detail_leads_summary as dls', 'dls.leads_master_id', '=', 'lm.id')
            ->selectRaw('
                COALESCE(dls.user_name, u.name) as user_name,
                lm.regional,
                lm.company_name,
                lm.email,
                lm.mobile_phone,
                lm.data_type,
                dls.flag_event,
                lm.created_at,
                COALESCE(dls.total_settlement_klien, 0) as total_settlement_klien,
                COALESCE(dls.saldo_utama, 0) as saldo_utama,
                lm.user_id
            ')
            ->whereIn('lm.regional', self::REGIONALS)
            ->whereNotNull('lm.user_id')
            ->whereNotNull('u.id')
            ->where('u.role', 'MPCC')
            ->orderByRaw('COALESCE(dls.total_settlement_klien, 0) desc')
            ->orderByRaw('COALESCE(dls.saldo_utama, 0) desc');

        if ($request->canvasser) {
            $query->where('lm.user_id', $request->canvasser);
        }

        if ($request->regional) {
            $query->where('lm.regional', $request->regional);
        }

        if ($request->flag_event) {
            $query->where('dls.flag_event', $request->flag_event);
        }

        if ($request->data_type) {
            $query->where('lm.data_type', $request->data_type);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('lm.created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        $fileName = 'area2-data-leads-' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $columns = [
            'Canvasser',
            'Regional',
            'Nama Perusahaan',
            'Email',
            'No HP',
            'Tipe Data',
            'Flag Event',
            'Tanggal Input',
            'Total Settlement',
            'Saldo Utama',
            'Rekomendasi',
        ];

        $callback = function () use ($query, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);

            foreach ($query->cursor() as $row) {
                $totalSettlement = $row->total_settlement_klien ?? 0;
                $saldoUtama = $row->saldo_utama ?? 0;
                $rekomendasi = ($saldoUtama >= 1000000) ? 'Push Campaign' : 'Push Topup';

                fputcsv($file, [
                    $row->user_name ?? '-',
                    $row->regional ?? '-',
                    $row->company_name ?? '-',
                    $row->email ?? '-',
                    $row->mobile_phone ?? '-',
                    $row->data_type ?? '-',
                    $row->flag_event ?? '-',
                    \Carbon\Carbon::parse($row->created_at)->format('Y-m-d'),
                    'Rp ' . number_format($totalSettlement, 0, ',', '.'),
                    'Rp ' . number_format($saldoUtama, 0, ',', '.'),
                    $rekomendasi,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
