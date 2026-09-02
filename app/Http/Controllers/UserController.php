<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\User;
use DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Session;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        logUserLogin();
        // $treg = DB::table('treg')->select('id', 'treg_name')->get();
        return view('auth.user');
    }
    public function getUsers(Request $request)
    {
        $data = DB::table('users')
            ->where('status', 'Aktif')
            ->select('id', 'name', 'email', 'nohp', 'status', 'role', 'area', 'branch', 'regional', 'referral_code', 'created_at')
            ->orderByDesc('created_at');

        return datatables()->of($data)
            // ->addColumn('treg_name', function ($row) {
            //     if ($row->role !== 'Treg') return '-';
            //     return DB::table('treg')->where('id', $row->treg_id)->value('treg_name');
            // })
            ->addColumn('action', function ($row) {
                return '
                <button class="btn btn-sm btn-warning editUser" data-id="' . $row->id . '">Edit</button>
                <button class="btn btn-sm btn-danger deleteUser" data-id="' . $row->id . '">Hapus</button>
            ';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'nohp'  => 'required',
            'email' => 'required|email|unique:users,email',
            'role'  => 'required|in:Admin,Tsel,Treg,cvsr,PH,TCD,Internal,b2b,Maxim,Automatech,GOTO,CDSI,KSS,Regional,Area 2,MPCC,SBP,Canvasser SBP,Dormant,1Synergy',
            'area' => 'nullable|required_if:role,MPCC',
            'branch' => 'nullable|required_if:role,MPCC',
            'regional' => 'nullable|required_if:role,Regional|string|max:255',
            'referral_code' => 'nullable|string|max:255',
            // 'treg_id' => 'nullable'
        ]);

        DB::table('users')->insert([
            'name' => $request->name,
            'nohp' => $request->nohp,
            'email' => $request->email,
            'role' => $request->role,
            'area' => $request->role === 'MPCC' ? $request->area : null,
            'branch' => $request->role === 'MPCC' ? $request->branch : null,
            'regional' => $request->role === 'Regional' ? $request->regional : null,
            'referral_code' => $request->role === 'MPCC' ? strtoupper(trim((string) $request->referral_code)) : null,
            // 'treg_id' => $request->role == 'Treg' ? $request->treg_id : null,
            'password' => bcrypt('123456'), // default
            'status' => 'Aktif',
            'created_at' => now()
        ]);

        return response()->json(['message' => 'User berhasil ditambahkan']);
    }

    // new: ambil data user untuk diedit
    public function editUser($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
        return response()->json($user);
    }

    // new: update user
    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required',
            'nohp'  => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'role'  => 'required|in:Admin,Tsel,Treg,cvsr,PH,TCD,Internal,b2b,Maxim,Automatech,GOTO,CDSI,KSS,Regional,Area 2,MPCC,SBP,Canvasser SBP,Dormant,1Synergy',
            'area' => 'nullable|required_if:role,MPCC',
            'branch' => 'nullable|required_if:role,MPCC',
            'regional' => 'nullable|required_if:role,Regional|string|max:255',
            'referral_code' => 'nullable|string|max:255',
            // 'treg_id' => 'nullable'
        ]);

        $data = [
            'name' => $request->name,
            'nohp' => $request->nohp,
            'email' => $request->email,
            'role' => $request->role,
            'area' => $request->role === 'MPCC' ? $request->area : null,
            'branch' => $request->role === 'MPCC' ? $request->branch : null,
            'regional' => $request->role === 'Regional' ? $request->regional : null,
            'referral_code' => $request->role === 'MPCC' ? strtoupper(trim((string) $request->referral_code)) : null,
            // 'treg_id' => $request->role == 'Treg' ? $request->treg_id : null,
            'updated_at' => now()
        ];

        DB::table('users')->where('id', $id)->update($data);

        return response()->json(['message' => 'User berhasil diperbarui']);
    }

    public function deleteUser($id)
    {
        DB::table('users')
            ->where('id', $id)
            ->update(['status' => 'Belum Aktif']);

        return response()->json(['message' => 'User berhasil di-nonaktifkan']);
    }
}
