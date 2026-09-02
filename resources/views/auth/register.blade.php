<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>MoniForm | Registration Page</title>
    <link rel="icon" href="CERITA white.png" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- iCheck Bootstrap -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/icheck-bootstrap/icheck-bootstrap.min.css') }}">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <style>
        .register-box {
            width: 800px;
        }
    </style>
</head>

<body class="hold-transition register-page">
    <div class="register-box">
        <div class="register-logo">
            <a href="#"><b>Tracers</b>Tambah User</a>
        </div>

        <div class="card">
            <div class="card-body register-card-body">
                <p class="login-box-msg">Register a new membership</p>

                <form action="{{ route('register.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>No HP</label>
                        <input type="number" name="nope" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="Admin">Admin</option>
                            <option value="Tsel">Tsel</option>
                            <option value="TCD">TCD</option>
                            <option value="Internal">Internal</option>
                            <option value="b2b">B2B</option>
                            <option value="CDSI">CDSI</option>
                            <option value="1Synergy">1Synergy</option>
                            <option value="KSS">KSS</option>
                            <option value="Regional">Regional</option>
                            <option value="Area 2">Area 2</option>
                            <option value="MPCC">MPCC</option>
                            <option value="SBP">SBP</option>
                            <option value="Canvasser SBP">Canvasser SBP</option>
                            <option value="Dormant">Dormant</option>
                        </select>
                    </div>

                    <div class="form-group" id="regional_group" style="display: none;">
                        <label>Regional</label>
                        <input type="text" name="regional" id="regional" class="form-control">
                    </div>


                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('vendor/adminlte/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            function toggleRoleFields() {
                const isRegional = $('#role').val() === 'Regional';
                $('#regional_group').toggle(isRegional);
                $('#regional').prop('required', isRegional);

                if (!isRegional) {
                    $('#regional').val('');
                }
            }

            $('#role').on('change', toggleRoleFields);
            toggleRoleFields();
        });
    </script>

    @if(session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session("success") }}',
                confirmButtonText: 'OK'
            });
        </script>
    @endif

</body>

</html>
