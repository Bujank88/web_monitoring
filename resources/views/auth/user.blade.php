@extends('master')
@section('title') Manajemen Users @endsection
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




<div class="spinner-wrapper">

    <div id="loading-overlay" style="display: none;">



        <div id="loading-message"
            style="font-size: 24px; color: white; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            Loading, please wait...

        </div>

    </div>

    <div class="card card-danger">

        <div class="card-header">
            <h3 style="font-weight: bold" class="card-title">MANAJEMEN USERS</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-primary btn-sm" id="btnTambahUser">
                    <i class="fas fa-plus"></i> Tambah User
                </button>
            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-sm w-100 table-striped table-hover"
                    id="datatablenya" style="font-size: 13px;">

                    <thead>
                        <tr>
                            <th class="text-center">No</th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">No HP</th>
                            <th class="text-center">Role</th>
                            {{-- <th class="text-center">Treg</th> --}}
                            <th class="text-center">Status</th>
                            <th class="text-center">Tanggal<br>Dibuat</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<!-- Modal Tambah/Edit User -->
<div class="modal fade" id="modalUser" tabindex="-1" role="dialog" aria-labelledby="modalUserLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUserLabel">Tambah User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUser">
                <div class="modal-body">
                    <input type="hidden" id="user_id" name="user_id">
                    
                    <div class="form-group">
                        <label for="name">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nohp">No HP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nohp" name="nohp" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role <span class="text-danger">*</span></label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Pilih Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Tsel">Tsel</option>
                            {{-- <option value="Treg">Treg</option> --}}
                            <option value="cvsr">Canvasser</option>
                            <option value="PH">PowerHouse</option>
                            <option value="TCD">TCD</option>
                            <option value="Internal">Internal</option>
                            <option value="b2b">B2B</option>
                            <option value="Maxim">Maxim</option>
                            <option value="Automatech">Automatech</option>
                            <option value="MPCC">MPCC</option>
                        </select>
                    </div>

                    <div id="mpcc_fields" style="display: none;">
                        <div class="form-group">
                            <label for="area">Area <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="area" name="area">
                        </div>

                        <div class="form-group">
                            <label for="branch">Branch <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="branch" name="branch">
                        </div>
                    </div>
                    
                    {{-- <div class="form-group" id="treg_group" style="display: none;">
                        <label for="treg_id">Treg <span class="text-danger">*</span></label>
                        <select class="form-control" id="treg_id" name="treg_id">
                            <option value="">Pilih Treg</option>
                            @foreach($treg as $t)
                                <option value="{{ $t->id }}">{{ $t->treg_name }}</option>
                            @endforeach
                        </select>
                    </div> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
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
                url: "{{ route('users.data') }}",
                type: "GET",
                dataSrc: function(json) {
                    console.log("Response dari server:", json);
                    return json.data || [];
                }
            },

            columns: [
                {
                    data: null,
                    name: 'no',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'name',
                    name: 'name',
                    orderable: true,
                    render: data => `<div style="text-align: left;">${data}</div>`
                },
                {
                    data: 'email',
                    name: 'email',
                    orderable: true,
                    render: data => `<div style="text-align: left;">${data}</div>`
                },
                {
                    data: 'nohp',
                    name: 'nohp',
                    orderable: true,
                    render: data => `<div style="text-align: center;">${data || '-'}</div>`
                },
                {
                    data: 'role',
                    name: 'role',
                    orderable: true,
                    render: function(data) {
                        let badgeClass = '';
                        if (data === 'Admin') {
                            badgeClass = 'badge-danger';
                        } else if (data === 'Tsel') {
                            badgeClass = 'badge-success';
                        } else if (data === 'cvsr') {
                            badgeClass = 'badge-secondary';
                        } else if (data === 'PH') {
                            badgeClass = 'badge-info';
                        } else if (data === 'Internal') {
                            badgeClass = 'badge-success';
                        } else if (data === 'b2b') {
                            badgeClass = 'badge-primary';
                        } else if (data === 'Maxim') {
                            badgeClass = 'badge-warning';
                        } else if (data === 'Automatech') {
                            badgeClass = 'badge-warning';
                        } else if (data === 'CDSI') {
                            badgeClass = 'badge-danger';
                        }
                        return `<div style="text-align: center;"><span class="badge ${badgeClass}">${data}</span></div>`;
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: true,
                    render: function(data) {
                        let badgeClass = data === 'Aktif' ? 'badge-success' : 'badge-warning';
                        return `<div style="text-align: center;"><span class="badge ${badgeClass}">${data}</span></div>`;
                    }
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    orderable: true,
                    render: function(data) {
                        if (!data) return '';
                        const dateObj = new Date(data);
                        if (isNaN(dateObj)) return data;
                        const day = String(dateObj.getDate()).padStart(2, '0');
                        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                        const month = monthNames[dateObj.getMonth()];
                        const year = dateObj.getFullYear();
                        return `<div style="text-align: center;">${day} ${month} ${year}</div>`;
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    render: data => `<div style="text-align: center;">${data}</div>`
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

        // Event handlers untuk modal dan CRUD operations
        $('#btnTambahUser').click(function() {
            $('#modalUser').modal('show');
            $('#modalUserLabel').text('Tambah User');
            $('#formUser')[0].reset();
            $('#user_id').val('');
            toggleRoleFields();
            // $('#treg_group').hide();
        });

        function toggleRoleFields() {
            const role = $('#role').val();
            const isMpcc = role === 'MPCC';

            $('#mpcc_fields').toggle(isMpcc);
            $('#area, #branch').prop('required', isMpcc);

            if (!isMpcc) {
                $('#area, #branch').val('');
            }

            if (role === 'Treg') {
                // $('#treg_group').show();
                // $('#treg_id').attr('required', true);
            } else {
                $('#treg_group').hide();
                // $('#treg_id').attr('required', false);
                // $('#treg_id').val('');
            }
        }

        $('#role').change(toggleRoleFields);

        // Form submit handler
        $('#formUser').submit(function(e) {
            e.preventDefault();
            
            let formData = $(this).serialize();
            let userId = $('#user_id').val();
            let url = userId ? `/users-update/${userId}` : "{{ route('users.store') }}";
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData + '&_token={{ csrf_token() }}',
                success: function(response) {
                    $('#modalUser').modal('hide');
                    table.ajax.reload();
                    
                    // Show success message
                    $('body').prepend(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 10px; right: 10px; z-index: 9999;">
                            ${response.message}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    `);
                    
                    // Auto hide after 3 seconds
                    setTimeout(function() {
                        $('.alert').fadeOut();
                    }, 3000);
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMsg = 'Terjadi kesalahan:\n';
                    
                    if (errors) {
                        Object.keys(errors).forEach(key => {
                            errorMsg += '- ' + errors[key][0] + '\n';
                        });
                    } else {
                        errorMsg = xhr.responseJSON.message || 'Terjadi kesalahan sistem';
                    }
                    
                    alert(errorMsg);
                }
            });
        });

        // Edit user handler
        $(document).on('click', '.editUser', function() {
            let userId = $(this).data('id');
            
            $.ajax({
                url: `/users-edit/${userId}`,
                type: 'GET',
                success: function(user) {
                    $('#modalUser').modal('show');
                    $('#modalUserLabel').text('Edit User');
                    
                    $('#user_id').val(user.id);
                    $('#name').val(user.name);
                    $('#email').val(user.email);
                    $('#nohp').val(user.nohp);
                    $('#role').val(user.role);
                    $('#area').val(user.area || '');
                    $('#branch').val(user.branch || '');
                    toggleRoleFields();
                    
                    if (user.role === 'Treg') {
                        $('#treg_group').show();
                        // $('#treg_id').attr('required', true);
                        // $('#treg_id').val(user.treg_id);
                    } else {
                        $('#treg_group').hide();
                        // $('#treg_id').attr('required', false);
                    }
                },
                error: function(xhr) {
                    alert('Gagal mengambil data user');
                }
            });
        });

        // Delete user handler
        $(document).on('click', '.deleteUser', function() {
            let userId = $(this).data('id');
            
            if (confirm('Apakah Anda yakin ingin menghapus user ini?')) {
                $.ajax({
                    url: `/users-delete/${userId}`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        table.ajax.reload();
                        
                        // Show success message
                        $('body').prepend(`
                            <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 10px; right: 10px; z-index: 9999;">
                                ${response.message}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        `);
                        
                        setTimeout(function() {
                            $('.alert').fadeOut();
                        }, 3000);
                    },
                    error: function(xhr) {
                        alert('Gagal menghapus user');
                    }
                });
            }
        });
    });
</script>

@endsection





