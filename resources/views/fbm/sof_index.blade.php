@extends('master')

@section('title', $pageTitle ?? 'List SOF')

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">{{ $pageTitle ?? 'List SOF' }}</h3>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="table-responsive">
            <table id="fbmSofTable" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Sender Name</th>
                        <th>Nomor WA</th>
                        <th>PIC</th>
                        <th>Verif Bisnis</th>
                        <th>Credit Line</th>
                        <th>Tanggal SOF</th>
                        @if($isAdmin)
                        <th>SOF</th>
                        @endif
                        <th>Dibuat</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(function () {
        $('#fbmSofTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('fbm.sof.data') }}',
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[7, 'desc']],
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'sender_name', name: 'sender_name' },
                { data: 'nomor_wa', name: 'nomor_wa' },
                { data: 'pic', name: 'pic' },
                { data: 'verif_bisnis', name: 'verif_bisnis' },
                { data: 'credit_line', name: 'credit_line' },
                { data: 'sof_uploaded_at', name: 'sof_uploaded_at' },
                @if($isAdmin)
                { data: 'sof_file_label', name: 'sof_file', orderable: false, searchable: false },
                @endif
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                zeroRecords: 'Data tidak ditemukan',
                paginate: {
                    previous: 'Prev',
                    next: 'Next'
                }
            }
        });
    });
</script>
@endsection
