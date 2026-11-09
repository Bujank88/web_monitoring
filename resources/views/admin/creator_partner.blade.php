@extends('master')
@section('title') Monitoring Creator Partner @endsection
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
<!-- Filter Dropdown Trigger -->
<div class="d-flex align-items-center mb-3" style="justify-content: flex-end;">
    <span style="font-weight: bold; font-size: 18px; margin-right: 10px;">Filter Data</span>
    <a href="#" id="filterDropdownToggle" style="color: #343a40; font-size: 22px;">
        <i class="fas fa-filter"></i>
    </a>
</div>

<!-- Filter Dropdown Content -->
<div id="filterDropdownContent" style="display: none; margin-bottom: 20px;">
    <div class="card card-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="area">Area</label>
                    <select id="area" name="area" class="form-control select2"
                        style="background-color: #313131; color: white;">
                        <option value="">Semua</option>
                        <option value="Area 1">Area 1</option>
                        <option value="Area 2">Area 2</option>
                        <option value="Area 3">Area 3</option>
                        <option value="Area 4">Area 4</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="region">Regional</label>
                    <select id="region" name="region" class="form-control select2"
                        style="background-color: #313131; color: white;">
                        <option value="">Semua</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="jenis_kol">Jenis KOL</label>
                    <select id="jenis_kol" name="jenis_kol" class="form-control select2"
                        style="background-color: #313131; color: white;">
                        <option value="">Semua</option>
                        <option value="KOL as a Buzzer">KOL as a Buzzer</option>
                        <option value="KOL as a Seller Online/Afiliate">KOL as a Seller Online</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="tier">Tier</label>
                    <select id="tier" name="tier" class="form-control select2"
                        style="background-color: #313131; color: white;">
                        <option value="">Semua</option>
                        <option value="Bronze">Bronze</option>
                        <option value="Silver">Silver</option>
                        <option value="Gold">Gold</option>
                        <option value="Platinum">Platinum</option>
                    </select>
                </div>

            </div>
        </div>
    </div>
</div>



<div class="spinner-wrapper">

    <div id="loading-overlay" style="display: none;">



        <div id="loading-message"
            style="font-size: 24px; color: white; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            Loading, please wait...

        </div>

    </div>

    <div class="card card-danger">

        <div class="card-header">

            <h3 style="font-weight: bold" class="card-title">DATA GFORM CREATOR PARTNER</h3>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm w-100 table-striped table-hover"
                    id="datatablenya" style="font-size: 13px;">

                    <thead>

                        <tr>
                            <th class="text-center">Tanggal<br>Isi Form</th>
                            <th class="text-center">Area</th>
                            <th class="text-center">Regional</th>
                            <th class="text-center">Jenis KOL</th>
                            <th class="text-center">Nama KOL</th>
                            <th class="text-center">Email<br>KOL</th>
                            <th class="text-center">No.HP KOL</th>
                            <th class="text-center">Referral<br> Code</th>
                            <th class="text-center">Total Invited</th>
                            <th class="text-center">Tier</th>

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
            serverSide: false, // karena kita mengirim semua data dari server sebagai JSON
            ordering: false,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            ajax: {
                url: "{{ route('creator_partner_data') }}",
                type: "GET",
                data: function(d) {
                    d.area = $('#area').val();
                    d.region = $('#region').val();
                    d.jenis_kol = $('#jenis_kol').val();
                    d.tier = $('#tier').val();
                },
                dataSrc: function(json) {
                    // support dua kemungkinan: { data: [...] } atau [...]
                    return (json && json.data) ? json.data : json || [];
                }
            },
            columns: [{
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data) {
                        if (!data) return '';
                        const dateObj = new Date(data);
                        if (isNaN(dateObj)) return data;
                        const day = String(dateObj.getDate()).padStart(2, '0');
                        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                        const month = monthNames[dateObj.getMonth()];
                        const year = dateObj.getFullYear();
                        return `<div style="text-align:center;">${day} ${month} ${year}</div>`;
                    }
                },
                {
                    data: 'area',
                    name: 'area',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'regional',
                    name: 'regional',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'jenis_kol',
                    name: 'jenis_kol',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'nama_kol',
                    name: 'nama_kol',
                    render: data => `<div style="text-align:left;">${data||''}</div>`
                },
                {
                    data: 'email_kol',
                    name: 'email_kol',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'no_hp_kol',
                    name: 'no_hp_kol',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'referral_code',
                    name: 'referral_code',
                    render: data => `<div style="text-align:center;">${data||''}</div>`
                },
                {
                    data: 'total_invited',
                    name: 'total_invited',
                    render: function(data, type, row) {
                        // Ambil tier dari row
                        let tier = (row.tier || '').toLowerCase();
                        let color = '';
                        switch (tier) {
                            case 'bronze':
                                color = '#cd7f32';
                                break;
                            case 'silver':
                                color = '#c0c0c0';
                                break;
                            case 'gold':
                                color = '#ffd700';
                                break;
                            case 'platinum':
                                color = '#b3e0ff';
                                break;
                            default:
                                color = '#888';
                        }
                        return `<div style="text-align:center; color:${color}; font-weight:bold;">${data || 0}</div>`;
                    }
                },
                {
                    data: 'tier',
                    name: 'tier',
                    render: function(data) {
                        let tier = (data || '').toLowerCase();
                        let icon = '';
                        let color = '';
                        let label = '';
                        switch (tier) {
                            case 'bronze':
                                icon = '<i class="fas fa-medal"></i>';
                                color = '#cd7f32';
                                label = 'Bronze';
                                break;
                            case 'silver':
                                icon = '<i class="fas fa-medal"></i>';
                                color = '#c0c0c0';
                                label = 'Silver';
                                break;
                            case 'gold':
                                icon = '<i class="fas fa-medal"></i>';
                                color = '#ffd700';
                                label = 'Gold';
                                break;
                            case 'platinum':
                                icon = '<i class="fas fa-gem"></i>';
                                color = '#b3e0ff';
                                label = 'Platinum';
                                break;
                            default:
                                icon = '<i class="fas fa-question-circle"></i>';
                                color = '#888';
                                label = '-';
                        }
                        return `<div style="text-align:center; color:${color}; font-weight:bold;">
                            ${icon} ${label}
                        </div>`;
                    }
                }
            ],
            preDrawCallback: function() {
                $('#loading-overlay').show();
            },
            drawCallback: function() {
                $('#loading-overlay').hide();
            }
        });
        // Event ketika dropdown filter berubah
        $('#area, #region, #jenis_kol, #tier').on('change', function() {
            table.ajax.reload();
        });
        $('#area').on('change', function() {
            let selectedArea = $(this).val();
            $.ajax({
                url: "{{ route('get_regionals') }}",
                type: "GET",
                data: {
                    area: selectedArea
                },
                success: function(regionals) {
                    let $regionSelect = $('#region');
                    $regionSelect.empty();
                    $regionSelect.append('<option value="">Semua</option>');

                    if (regionals.length > 0) {
                        regionals.forEach(function(regional) {
                            $regionSelect.append(`<option value="${regional}">${regional}</option>`);
                        });
                    }

                    // Reset ke "Semua" setiap kali area berubah
                    $regionSelect.val("");

                    // Reload datatable biar langsung sesuai filter baru
                    $('#datatablenya').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    console.error("Gagal ambil regional:", xhr.responseText);
                }
            });
        });


        // Event ketika dropdown filter berubah
        $('#area, #region, #jenis_kol, #tier').on('change', function() {
            table.ajax.reload();
        });

        $('#datatablenya').on('error.dt', function(e, settings, techNote, message) {
            console.error("DataTables Error:", message);
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Toggle filter dropdown
    $(document).ready(function() {
        $('#filterDropdownToggle').on('click', function(e) {
            e.preventDefault();
            $('#filterDropdownContent').slideToggle(200);
        });
    });
</script>

@endsection