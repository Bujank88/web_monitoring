@extends('master')
@section('title') {{ $judul }} - Project Area 3 @endsection

@section('css')
<style>
    .tbl-project {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .tbl-project th,
    .tbl-project td {
        border: 1px solid #fff;
        padding: 8px 10px;
        text-align: center;
        vertical-align: middle;
    }
    /* Judul tabel */
    .tbl-project .judul {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        font-weight: 700;
        font-size: 11px;
        letter-spacing: .5px;
        text-transform: uppercase;
    }
    /* Header kolom */
    .tbl-project thead .head-kolom th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        height: 45px;
    }
    /* Baris data belang */
    .tbl-project tbody tr:nth-child(odd)  td {color: #0d6efd; font-weight: 700; background: #f2f2f2; }
    .tbl-project tbody tr:nth-child(even) td {color: #0d6efd; font-weight: 700; background: #ffffff; }
    .tbl-project tbody tr:hover td { background: #e2e2e2; }
    .tbl-project .col-nama {
        color: #000;
        font-weight: 700;
    }
    /* Baris total */
    .tbl-project tfoot td {
        background: #ffee00;
        color: #000;
        font-weight: 700;
        font-size: 12px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="table-responsive">
        <table class="tbl-project">
            <thead>
                <tr>
                    <th colspan="3" class="judul">
                        Data {{ $judul }} | Project Area 3
                    </th>
                </tr>
                <tr class="head-kolom">
                    <th style="width:70px;">No</th>
                    <th>Nama</th>
                    <th>Lokasi Kerja</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="col-nama">{{ $row->nama }}</td>
                    <td>{{ $row->lokasi_kerja }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-muted">Belum ada data</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection