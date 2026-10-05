<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupervisorController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'canvasser' => 'nullable|integer',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'q' => 'nullable|string|max:100',
        ]);
        $members = User::where('role', 'cvsr')->where('supervisor_id', $request->user()->id)
            ->orderBy('name')->get(['id', 'name', 'status']);
        $query = DB::table('leads_master as l')->whereIn('l.user_id', $members->pluck('id'));
        if (!empty($filters['canvasser'])) {
            $query->where('l.user_id', $filters['canvasser']);
        }
        if (!empty($filters['from'])) {
            $query->where('l.created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (!empty($filters['to'])) {
            $query->where('l.created_at', '<', \Carbon\Carbon::parse($filters['to'])->addDay()->format('Y-m-d'));
        }
        if (!empty($filters['q'])) {
            $query->where(function ($query) use ($filters) {
                $query->where('l.company_name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('l.email', 'like', '%'.$filters['q'].'%');
            });
        }
        $summary = (clone $query)->selectRaw("l.user_id, COUNT(*) as total, SUM(CASE WHEN l.data_type = 'Leads' THEN 1 ELSE 0 END) as leads, SUM(CASE WHEN l.data_type = 'Eksisting Akun' THEN 1 ELSE 0 END) as accounts")
            ->groupBy('l.user_id')->get()->keyBy('user_id');
        $leads = $query->join('users as u', 'u.id', '=', 'l.user_id')
            ->select('l.*', 'u.name as canvasser_name')->orderByDesc('l.id')->paginate(25)->withQueryString();

        return view('supervisor.index', compact('members', 'summary', 'leads'));
    }

    public function team()
    {
        $supervisors = User::where('role', 'Supervisor')->orderBy('name')->get(['id', 'name']);
        $canvassers = User::where('role', 'cvsr')->orderBy('name')->get(['id', 'name', 'email', 'supervisor_id']);

        return view('supervisor.team', compact('supervisors', 'canvassers'));
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'canvasser_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'cvsr')],
            'supervisor_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'Supervisor')->where('status', 'Aktif')],
        ]);
        User::whereKey($data['canvasser_id'])->update(['supervisor_id' => $data['supervisor_id'] ?? null]);

        return back()->with('success', 'PIC canvasser berhasil diperbarui.');
    }
}
