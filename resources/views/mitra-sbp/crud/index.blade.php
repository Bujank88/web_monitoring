@extends('master')
@section('title') Configuration - Mitra SBP @endsection

@section('css')
<link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet"/>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Configuration Mitra SBP</h4>
            <a href="{{ route('configuration.mitra-sbp.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Tambah Data
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="mitraSbpTable">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Reg ID</th>
                            <th>Email MyAds</th>
                            <th>Area</th>
                            <th>Regional</th>
                            <th>Remark</th>
                            <th>Voucher</th>
                            <th style="width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item->id }}</td>
                                <td>{{ $item->reg_id ?? '-' }}</td>
                                <td>{{ $item->email_myads }}</td>
                                <td>{{ $item->area ?? '-' }}</td>
                                <td>{{ $item->regional ?? '-' }}</td>
                                <td>{{ $item->remark ?? '-' }}</td>
                                <td>{{ $item->voucher ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('configuration.mitra-sbp.edit', $item->id) }}" class="btn btn-warning btn-xs">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Data tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    $(function () {
        $('#mitraSbpTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    });
</script>
@endsection
