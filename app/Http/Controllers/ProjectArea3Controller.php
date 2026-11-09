<?php

namespace App\Http\Controllers;

use App\Models\ProjectArea3Member;

class ProjectArea3Controller extends Controller
{
    public function ds()
    {
        return $this->tampil('ds', 'Project DS');
    }

    public function canvasser()
    {
        return $this->tampil('canvasser', 'Canvasser');
    }

    private function tampil(string $tipe, string $judul)
    {
        $data = ProjectArea3Member::where('tipe', $tipe)->orderBy('nama')->get();

        $total     = $data->count();
        $perLokasi = $data->groupBy('lokasi_kerja')->map->count()->sortDesc();

        return view('project-area3.index', compact('data', 'total', 'perLokasi', 'judul'));
    }
}