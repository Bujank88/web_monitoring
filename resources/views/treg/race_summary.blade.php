@extends('master')
@section('title') Adsvantage Race: TREG Summary @endsection
@section('css')

<style>
    .btn-ref {
        position: fixed;
        top: 50px;
        left: 1000px;
    }

    #loading-overlay {

        position: fixed;

        top: 0;

        left: 0;

        width: 100%;

        height: 100%;

        background: rgba(0, 0, 0, 0.7);

        z-index: 9999;

        display: none;

    }

    .table {
        background-color: #f9f9f9;
        border-radius: 8px;
        overflow: hidden;
        width: 100%;
        max-width: 100%;
        margin-top: 15px;
        border: 0.5px solid #ccc;
        table-layout: auto;

        /* Allow dynamic column sizing */
    }

    .table th,
    .table td {
        padding: 8px !important;
        font-size: 16px;
        border: 0.5px solid #ccc;
        color: #313131;
        text-align: center;
    }

    .table th {
        font-weight: bold;
        text-align: center;
        background-color: #343a40;
        color: #ffffff;
        /* Align text to the left */
    }


    .table tbody tr {
        transition: background-color 0.3s;
    }

    .table tbody tr:hover {
        background-color: #6b6b6bff;
    }

    .table tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
        /* Odd row background color */
    }

    .table tbody tr:nth-child(even) {
        background-color: #ffffff;
        /* Even row background color */
    }

    @media (max-width: 768px) {

        .table th,
        .table td {
            font-size: 12px;
        }
    }

    /* Custom tier badge styles */
    .tier-black {
        background-color: #343a40 !important;
        color: white !important;
    }
    .tier-bronze {
        background-color: #cd7f32 !important;
        color: white !important;
    }
    .tier-silver {
        background-color: #c0c0c0 !important;
        color: #333 !important;
    }
    .tier-gold {
        background-color: #ffd700 !important;
        color: #333 !important;
    }
    .tier-platinum {
        background-color: #e5e4e2 !important;
        color: #333 !important;
        border: 1px solid #ccc !important;
    }
    .tier-diamond {
        background: linear-gradient(45deg, #b9f2ff, #66d9ff) !important;
        color: #333 !important;
    }
</style>
@endsection

@section('content')
@if(session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif

<div class="spinner-wrapper">

    <div id="loading-overlay" style="display: none;">



        <div id="loading-message"
            style="font-size: 24px; color: white; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            Loading, please wait...

        </div>

    </div>

    <div class="card card-danger">

        <div class="card-header">
            <h3 style="font-weight: bold" class="card-title">SUMMARY ACQUISITION RACE BY TREG</h3>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm w-100 table-striped table-hover"
                    id="datatablenya" style="font-size: 13px;">

                    <thead>
                        <tr>
                            <th class="text-center">TREG</th>
                            <th class="text-center">Kode<br>Voucher</th>
                            <th class="text-center">Jumlah<br>Akuisisi</th>
                            <th class="text-center">Total<br>Top Up</th>
                            <th class="text-center">Tier<br>Remarks</th>
                        </tr>
                    </thead>

                    <tbody>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection
@section('js')
<script>
    $(document).ready(function() {
        var table = $('#datatablenya').DataTable({

            processing: true,

            serverSide: true,

            ordering: false,

            orderable: false,

            lengthMenu: [

                [10, 25, 50, 100],

                [10, 25, 50, 100]

            ],

            ajax: {
                url: "{{ route('treg_summary_data') }}",
                type: "GET",
                dataSrc: function(json) {
                    console.log("Response dari server:", json);
                    return json.data || [];
                }
            },

            columns: [
                {
                    data: 'treg_name',
                    name: 'treg_name',
                    orderable: true,
                    render: data => `<div style="text-align: center;"><strong>${data}</strong></div>`
                },
                {
                    data: 'voucher_code',
                    name: 'voucher_code',
                    orderable: true,
                    render: data => `<div style="text-align: center;"><strong style="font-family: Arial, sans-serif; font-size: 14px; letter-spacing: 1px;">${data}</strong></div>`
                },
                {
                    data: 'jumlah_akuisisi',
                    name: 'jumlah_akuisisi',
                    orderable: true,
                    render: function(data) {
                        return `<div style="text-align: center;"><span class="badge badge-info">${data || 0}</span></div>`;
                    }
                },
                {
                    data: 'total_topup',
                    name: 'total_topup',
                    orderable: true,
                    render: function(data) {
                        const formatted = new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0
                        }).format(data || 0);
                        return `<div style="text-align: center;"><strong>${formatted}</strong></div>`;
                    }
                },
                {
                    data: 'tier_remarks',
                    name: 'tier_remarks',
                    orderable: true,
                    render: function(data, type, row) {
                        let badgeClass = '';
                        let tierText = '';
                        let incentive = '';
                        
                        const jumlah = parseInt(row.jumlah_akuisisi || 0);
                        const totalTopup = parseFloat(row.total_topup || 0);
                        
                        if (jumlah >= 1 && jumlah <= 2) {
                            badgeClass = 'tier-black';
                            tierText = 'BLACK';
                            incentive = `1% = ${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 0}).format(totalTopup * 0.01)}`;
                        } else if (jumlah >= 3 && jumlah <= 9) {
                            badgeClass = 'tier-bronze';
                            tierText = 'BRONZE';
                            incentive = `2% = ${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 0}).format(totalTopup * 0.02)}`;
                        } else if (jumlah >= 10 && jumlah <= 15) {
                            badgeClass = 'tier-silver';
                            tierText = 'SILVER';
                            incentive = `3% = ${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 0}).format(totalTopup * 0.03)}`;
                        } else if (jumlah >= 16 && jumlah <= 25) {
                            badgeClass = 'tier-gold';
                            tierText = 'GOLD';
                            incentive = `4% = ${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 0}).format(totalTopup * 0.04)}`;
                        } else if (jumlah >= 26 && jumlah <= 50) {
                            badgeClass = 'tier-platinum';
                            tierText = 'PLATINUM';
                            incentive = `5% = ${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 0}).format(totalTopup * 0.05)}`;
                        } else if (jumlah > 50) {
                            badgeClass = 'tier-diamond';
                            tierText = 'DIAMOND';
                            incentive = `6% = ${new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', minimumFractionDigits: 0}).format(totalTopup * 0.06)}`;
                        } else {
                            badgeClass = 'badge-danger';
                            tierText = 'NO TIER';
                            incentive = '0%';
                        }
                        
                        return `<div style="text-align: center;">
                            <span class="badge ${badgeClass}">${tierText}</span><br>
                            <small style="color: #666;"><strong>${incentive}</strong></small>
                        </div>`;
                    }
                }
            ],

            preDrawCallback: function(settings) {

                $('#loading-overlay').show();

            },

            drawCallback: function(settings) {

                $('#loading-overlay').hide();

            }

        });
        $('#datatablenya').on('error.dt', function(e, settings, techNote, message) {
            console.log("DataTables Error:", message);
        });
    });
</script>

@endsection