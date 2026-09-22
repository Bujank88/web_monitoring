@extends('master')

@section('title', $pageTitle ?? 'Data Leads by Referral')

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">{{ $pageTitle ?? 'Data Leads by Referral' }}</h3>
    </div>
    <div class="card-body">
        @if($canFilterAll)
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="filterReferralId">Referral</label>
                <select id="filterReferralId" class="form-control">
                    <option value="">Semua Referral</option>
                    @foreach($referralOptions as $referral)
                    <option value="{{ $referral->id }}">{{ $referral->referral_code }} - {{ $referral->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="filterCreatedBy">Canvasser SBP</label>
                <select id="filterCreatedBy" class="form-control">
                    <option value="">Semua Canvasser SBP</option>
                    @foreach($canvasserOptions as $canvasser)
                    <option value="{{ $canvasser->id }}">{{ $canvasser->name }} - {{ $canvasser->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="filterSearchCompany">Nama Perusahaan</label>
                <input type="text" id="filterSearchCompany" class="form-control" placeholder="Cari nama perusahaan">
            </div>
        </div>
        @endif

        <div class="table-responsive">
            <table id="tableDataLeadsByReferral" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Canvasser SBP</th>
                        <th>Code Referral</th>
                        <th>Nama Referral</th>
                        <th>Nama Perusahaan</th>
                        <th>Email MyAds</th>
                        <th>No Telp</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leadRows as $index => $row)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td data-created-by="{{ $row->created_by ?? '' }}">{{ $row->canvasser_name ?? '-' }}</td>
                        <td data-referral-id="{{ $row->referral_id ?? '' }}">{{ $row->referral_code }}</td>
                        <td>{{ $row->referral_name }}</td>
                        <td>{{ $row->company_name }}</td>
                        <td>{{ $row->email_myads }}</td>
                        <td>{{ $row->mobile_phone }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(function () {
        const canFilterAll = @json($canFilterAll);

        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'tableDataLeadsByReferral') {
                return true;
            }

            if (!canFilterAll) {
                return true;
            }

            const referralId = $('#filterReferralId').val();
            const createdBy = $('#filterCreatedBy').val();
            const node = $('#tableDataLeadsByReferral').DataTable().row(dataIndex).node();

            if (referralId) {
                const currentReferralId = $('td:eq(2)', node).data('referral-id');
                if (String(currentReferralId) !== String(referralId)) {
                    return false;
                }
            }

            if (createdBy) {
                const currentCreatedBy = $('td:eq(1)', node).data('created-by');
                if (String(currentCreatedBy) !== String(createdBy)) {
                    return false;
                }
            }

            return true;
        });

        const table = $('#tableDataLeadsByReferral').DataTable({
            order: [[0, 'asc']]
        });

        if (canFilterAll) {
            $('#filterReferralId').on('change', function () {
                table.draw();
            });

            $('#filterCreatedBy').on('change', function () {
                table.draw();
            });
        }

        $('#filterSearchCompany').on('keyup', function () {
            table.column(4).search(this.value).draw();
        });
    });
</script>
@endpush
