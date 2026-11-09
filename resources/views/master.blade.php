<!DOCTYPE html>

<html>



<head>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">

    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>TRACERS | @yield('title')</title>




    <!-- Tell the browser to be responsive to screen width -->

    <meta name="viewport" content="width=device-width, initial-scale=1">

    @yield('css')

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

    <!-- AdminLTE -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

    <!-- Nice Select -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-nice-select/1.1.0/css/nice-select.css"
        integrity="sha512-uHuCigcmv3ByTqBQQEwngXWk7E/NaPYP+CFglpkXPnRQbSubJmEENgh+itRDYbWV0fUZmUz7fD/+JDdeQFD5+A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" type="image/png" href="{{ asset('images/TRACERS_3.png') }}">


    <style>
        .loader {

            width: 48px;

            height: 48px;

            border: 5px solid #FFF;

            border-bottom-color: #124170;

            border-radius: 50%;

            display: inline-block;

            box-sizing: border-box;

            animation: rotation 1s linear infinite;

        }



        @keyframes rotation {

            0% {

                transform: rotate(0deg);

            }



            100% {

                transform: rotate(360deg);

            }

        }



        .loader1 {

            width: 48px;

            height: 48px;

            border: 5px solid #FFF;

            border-bottom-color: #124170;

            border-radius: 50%;

            display: inline-block;

            box-sizing: border-box;

            animation: rotation 1s linear infinite;

        }



        @keyframes rotation {

            0% {

                transform: rotate(0deg);

            }



            100% {

                transform: rotate(360deg);

            }

        }



        .br {

            border: 0px solid red;

        }



        .bgmer {

            background-color: #b92c10 !important;

            color: white !important;

            border: 1px solid #EB4E2D !important;

        }



        .bghit {

            background-color: #141414 !important;

            color: white !important;

            border: 1px solid #141414 !important;

        }



        .page-item.active .page-link {

            color: #fff !important;

            background-color: #000 !important;

            border-color: #000 !important;

        }



        .page-link {

            color: #000 !important;

            background-color: #fff !important;

            border: 1px solid #dee2e6 !important;

        }



        .page-link:hover {

            color: #fff !important;

            background-color: #000 !important;

            border-color: #000 !important;

        }
    </style>


</head>



<body class="hold-transition sidebar-mini">



    <div class="wrapper">

        <!-- Navbar -->

        @include('navbar')

        <!-- /.navb ar -->



        @include('sidebar')

        <!-- Content Wrapper. Contains page content -->

        <div class="content-wrapper">

            <!-- Content Header (Page header) -->

            <section class="content-header">

                <div class="container-fluid">



                </div><!-- /.container-fluid -->

            </section>



            <!-- Main content -->

            <section class="content">

                <div class="row">

                    <div class="col-12">



                        {{-- @if ($errors->any())

                        <div class="alert alert-danger d-flex align-items-center" role="alert">

                            <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:">

                                <use xlink:href="#exclamation-triangle-fill"></use>

                            </svg>

                            <ul>

                                @foreach ($errors->all() as $error)

                                <div>

                                    <li>{{ $error }}</li>

                    </div>



                    @endforeach

                    </ul>

                </div>

                @endif



                @if(Session('sukses'))



                <div class="alert alert-success alert-dismissible">

                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>

                    <h5><i class="icon fas fa-check"></i> Berhasil!</h5>

                    {{Session::get('sukses')}}



                </div>



                @endif --}}



                @yield('content')



                <!-- /.card -->

        </div>

        <!-- /.col -->

    </div>

    <!-- /.row -->

    </section>

    <!-- /.content -->

    </div>

    <!-- /.content-wrapper -->

    <footer class="main-footer text-right">
        <div class="float-left d-none d-sm-block">
            <b>Version</b> 1.3.2
        </div>
        <strong>&copy; 2025 <a href="/">TRACERS</a></strong>
    </footer>




    <!-- Control Sidebar -->

    <aside class="control-sidebar control-sidebar-dark">

        <!-- Control sidebar content goes here -->

    </aside>

    <!-- /.control-sidebar -->

    </div>

    <!-- ./wrapper -->







    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap 4 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

    <!-- AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toastr -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


    @yield('js')


</body>



</html>