<?php

namespace App\Http\Controllers;

use App\Models\FbmSof;
use App\Support\SupervisorTeam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupervisorReportController extends Controller
{
    private function filters(Request $request): array
    {
        return $request->validate([
            'canvasser' => 'nullable|integer',
            'month' => 'nullable|date_format:Y-m',
        ]);
    }

    private function members(Request $request)
    {
        return SupervisorTeam::members()->when($request->filled('canvasser'), fn ($q) => $q->where('id', $request->canvasser));
    }

    private function page(Request $request, string $title, array $columns, $rows, string $note = '')
    {
        $members = SupervisorTeam::members()->orderBy('name')->get(['id', 'name']);

        return view('supervisor.report', compact('title', 'columns', 'rows', 'members', 'note'));
    }

    public function daily()
    {
        return redirect()->route('daily.topup.channel', request()->query());
    }

    public function canvassers()
    {
        return redirect()->route('admin.home', request()->query());
    }

    public function logbook(Request $request, string $period)
    {
        $this->filters($request);
        abort_unless(in_array($period, ['monthly', 'daily'], true), 404);
        $table = $period === 'monthly' ? 'logbook' : 'logbook_daily';
        $start = Carbon::parse(($request->month ?: now()->format('Y-m')).'-01');
        $query = DB::table($table.' as b')->join('leads_master as l', 'l.id', '=', 'b.leads_master_id')
            ->join('users as u', 'u.id', '=', 'l.user_id')->whereIn('l.user_id', $this->members($request)->select('id'));
        if ($period === 'monthly') {
            $query->where('b.bulan', $start->month)->where('b.tahun', $start->year);
        } else {
            $query->where('b.created_at', '>=', $start->toDateString())->where('b.created_at', '<', $start->copy()->addMonth()->toDateString());
        }
        $rows = $query->select('u.name as canvasser', 'l.company_name', 'l.email', 'b.komitmen', 'b.plan_min_topup', 'b.status', 'b.created_at')->orderByDesc('b.id')->paginate(25)->withQueryString();

        return $this->page($request, 'Logbook '.ucfirst($period), ['canvasser' => 'Canvasser', 'company_name' => 'Perusahaan', 'email' => 'Email', 'komitmen' => 'Komitmen', 'plan_min_topup' => 'Rencana Top Up (Rp)', 'status' => 'Status', 'created_at' => 'Dibuat'], $rows, 'Monitoring logbook anggota binaan.');
    }

    public function sof(Request $request)
    {
        $this->filters($request);
        $start = Carbon::parse(($request->month ?: now()->format('Y-m')).'-01');
        $query = FbmSof::whereIn('pic', SupervisorTeam::uniqueNames());
        if ($request->filled('canvasser')) {
            $query->whereIn('pic', $this->members($request)->select('name'));
        }
        $rows = $query->where('created_at', '>=', $start->toDateString())->where('created_at', '<', $start->copy()->addMonth()->toDateString())
            ->select('pic', 'sender_name', 'nomor_wa', 'waba_id', 'verif_bisnis', 'credit_line', 'created_at')->orderByDesc('id')->paginate(25)->withQueryString();

        return $this->page($request, 'List SOF', ['pic' => 'Canvasser', 'sender_name' => 'Sender Name', 'nomor_wa' => 'Nomor WA', 'waba_id' => 'WABA ID', 'verif_bisnis' => 'Verifikasi Bisnis', 'credit_line' => 'Credit Line', 'created_at' => 'Dibuat'], $rows, 'SOF anggota binaan, berdasarkan bulan pengajuan.');
    }

    public function referral()
    {
        return redirect()->route('admin.monitoring.canvasser_voucher', request()->query());
    }

    public function createSof()
    {
        $members = SupervisorTeam::members()->whereIn('name', SupervisorTeam::uniqueNames())->where('status', 'Aktif')->orderBy('name')->get(['id', 'name']);

        return view('supervisor.sof-create', compact('members'));
    }

    public function storeSof(Request $request)
    {
        $data = $request->validate([
            'canvasser_id' => 'required|integer', 'sender_name' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:50', 'waba_id' => 'nullable|string|max:255',
            'verif_bisnis' => 'required|in:Yes,On Progress,No',
        ]);
        $member = SupervisorTeam::members()->whereIn('name', SupervisorTeam::uniqueNames())->where('status', 'Aktif')->find($data['canvasser_id']);
        abort_unless($member, 403);
        FbmSof::create([
            'sender_name' => trim($data['sender_name']), 'nomor_wa' => trim($data['nomor_wa']),
            'waba_id' => $data['waba_id'] ?? null, 'pic' => $member->name,
            'verif_bisnis' => $data['verif_bisnis'], 'credit_line' => 'No',
        ]);

        return redirect()->route('supervisor.sof')->with('success', 'Pengajuan SOF anggota berhasil disimpan.');
    }
}
