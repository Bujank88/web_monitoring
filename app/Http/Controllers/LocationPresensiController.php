<?php

namespace App\Http\Controllers;

use App\Models\LocationPresensiCvsr;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationPresensiController extends Controller
{
    /**
     * Display a listing of the resource - Halaman admin input lokasi CVSR
     */
    public function index()
    {
        // Hanya Admin yang bisa akses
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Unauthorized');
        }

        // Ambil semua CVSR dengan lokasi mereka
        $cvsrs = User::where('role', 'cvsr')
            ->with('locationPresensi')
            ->orderBy('name')
            ->get();

        return view('absensi.insert_location', compact('cvsrs'));
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'Admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $location = LocationPresensiCvsr::updateOrCreate(
                ['user_id' => $request->user_id],
                [
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'keterangan' => $request->keterangan,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil disimpan',
                'data' => $location
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan lokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'Admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $location = LocationPresensiCvsr::where('user_id', $id)->firstOrFail();
            
            $location->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'keterangan' => $request->keterangan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil diupdate',
                'data' => $location
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate lokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy($id)
    {
        if (Auth::user()->role !== 'Admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            LocationPresensiCvsr::where('user_id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lokasi berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus lokasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle location requirement status
     */
    public function toggleLocationNeeded($id)
    {
        if (Auth::user()->role !== 'Admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $location = LocationPresensiCvsr::where('user_id', $id)->firstOrFail();
            $newStatus = !$location->is_location_needed;
            
            $location->update(['is_location_needed' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => $newStatus 
                    ? 'Pengecekan lokasi diaktifkan' 
                    : 'Pengecekan lokasi dinonaktifkan',
                'is_location_needed' => $newStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status lokasi: ' . $e->getMessage()
            ], 500);
        }
    }
}
