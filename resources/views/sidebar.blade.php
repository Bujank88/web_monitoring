<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ url('/') }}" class="brand-link">
        <img src="{{ asset('images/TRACERS_2.png') }}" alt="MyAds Logo" class="brand-image img-circle elevation-2">
        <span class="brand-text font-weight-bold">{{ Auth::user()->role }}</span>
    </a>

    <div class="sidebar">
        <!-- Sidebar user -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="info">
                <span class="badge badge-danger">{{ Str::limit(Auth::user()->name, 25) }}</span><br>
                @php
                $user = Auth::user();
                $isAdmin = $user->role === 'Admin';
                $isTsel = $user->role === 'Tsel';
                $isTreg = $user->role === 'Treg';
                $isCanv = $user->role === 'cvsr';
                $isPH = $user->role === 'PH';
                @endphp

                @if($isAdmin && $user->email === 'admin@telkomsel.co.id')
                <span class="badge badge-warning">SUPER ADMIN</span>
                @elseif($isAdmin)
                <span class="badge badge-warning">ADMIN</span>
                @elseif($isTsel)
                <span class="badge badge-success">TSEL</span>
                @elseif($isPH)
                <span class="badge badge-info">POWERHOUSE</span>
                @elseif($isCanv)
                <span class="badge badge-primary">CANVASSER</span>
                @elseif($isTreg)
                @php
                $treg_name = DB::table('treg')->where('id', $user->treg_id)->value('treg_name');
                @endphp
                <span class="badge badge-info">TREG {{ $treg_name ?? '-' }}</span>
                @endif
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                {{-- ===== ADMIN: bisa akses semua menu (ALL + TREG) ===== --}}
                @if($isAdmin || $isTsel || $isCanv || $isPH)
                <li class="nav-header">ALL DASHBOARD</li>
                <li class="nav-item">
                    <a href="{{ route('daily.topup.channel') }}"
                        class="nav-link waves-effect {{ request()->routeIs('daily.topup.channel') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-chart-line" style="color:rgb(255, 159, 64);"></i>
                        <p>Daily Top Up Channel</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.home') }}"
                        class="nav-link waves-effect {{ request()->routeIs('admin.home') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-table-cells" style="color:rgb(255, 255, 255);"></i>
                        <p>Report Canvasser</p>
                    </a>
                </li>
                @endif
                @if($isAdmin || $isTsel || $isPH)
                <li class="nav-item has-treeview 
                    {{ (request()->routeIs('region-target') || request()->routeIs('admin.monitoring.powerhouse_referral')) ? 'menu-open' : '' }}">
                    
                    <a href="#" class="nav-link 
                        {{ (request()->routeIs('region-target') || request()->routeIs('admin.monitoring.powerhouse_referral')) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-house-chimney-user"></i>
                        <p>
                            Powerhouse
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <!-- Report Powerhouse -->
                        <li class="nav-item">
                            <a href="{{ route('region-target') }}"
                            class="nav-link {{ request()->routeIs('region-target') ? 'active' : '' }} ml-2">
                                <i class="nav-icon fa-solid fa-chart-line" style="color:rgb(240, 37, 1);"></i>
                                <p>Report Powerhouse</p>
                            </a>
                        </li>

                        <!-- Powerhouse Referral -->
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.powerhouse_referral') }}"
                            class="nav-link {{ request()->routeIs('admin.monitoring.powerhouse_referral') ? 'active' : '' }} ml-2">
                                <i class="nav-icon fas fa-star" style="color:#ffc107;"></i>
                                <p>PowerHouse Referral</p>
                            </a>
                        </li>
                    </ul>
                </li>

                @endif
                {{--<li class="nav-item {{ (request()->routeIs('admin.voucher') || request()->routeIs('admin.claim.voucher')) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link waves-effect {{ (request()->routeIs('admin.voucher') || request()->routeIs('admin.claim.voucher')) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-ticket-alt" style="color:#17a2b8;"></i>
                        <p>Manajemen Voucher <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.voucher') }}"
                                class="nav-link waves-effect {{ request()->routeIs('admin.voucher') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fas fa-clipboard-list nav-icon" style="color:#17a2b8;"></i>
                                <p>Daftar Voucher</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.claim.voucher') }}"
                                class="nav-link waves-effect {{ request()->routeIs('admin.claim.voucher') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fas fa-hand-holding-usd nav-icon" style="color:#28a745;"></i>
                                <p>Klaim Voucher</p>
                            </a>
                        </li>
                    </ul>
                </li> --}}


                {{-- <li class="nav-header">Report</li> --}}
                <li class="nav-item">
                    <a href="{{ route('leads-master.index') }}"
                        class="nav-link waves-effect {{ request()->routeIs('leads-master.index') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-star" style="color:rgb(240,236,1);"></i>
                        <p>Data Leads & Akun</p>
                    </a>
                </li>     
                <li class="nav-item">
                    <a href="{{ route('leads-master.create') }}"
                        class="nav-link waves-effect {{ request()->routeIs('leads-master.create') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-user-pen" style="color:rgb(1, 240, 172);"></i>
                        <p>New Leads</p>
                    </a>
                </li>      
                <li class="nav-item">
                    <a href="{{ route('leads-master.create-existing') }}"
                        class="nav-link waves-effect {{ request()->routeIs('leads-master.create-existing') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-user-tie" style="color:rgb(143, 142, 142);"></i>
                        <p>New/Eksisting Akun</p>
                    </a>
                </li>         
                @if(!$isCanv)
                <li class="nav-item">
                    <a href="{{ route('calendar') }}"
                        class="nav-link waves-effect {{ request()->routeIs('calendar') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-calendar" style="color:rgb(178, 192, 126);"></i>
                        <p>Jadwal</p>
                    </a>
                </li>  
                @endif
                @php
                    $logbookActive = request()->routeIs('logbook.*') || request()->routeIs('logbook-daily.*');
                @endphp

                <li class="nav-item {{ $logbookActive ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ $logbookActive ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book" style="color:#eafc4f;"></i>
                        <p>
                            Logbook
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('logbook.index') }}"
                            class="nav-link {{ request()->routeIs('logbook.index') ? 'active' : '' }}" style="padding-left: 20px;">
                                <i class="nav-icon fa-solid fa-book" style="color:rgb(90,90,250);"></i>
                                <p>Logbook Monthly</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('logbook-daily.index') }}"
                            class="nav-link {{ request()->routeIs('logbook-daily.index') ? 'active' : '' }}" style="padding-left: 20px;">
                                <i class="nav-icon fa-solid fa-book" style="color:rgb(250, 125, 90);"></i>
                                <p>Logbook Daily</p>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- <li class="nav-item">
                    <a href="{{ route('topup-canvasser') }}"
                        class="nav-link waves-effect {{ request()->routeIs('topup-canvasser') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-money-bill" style="color:rgb(80, 255, 80);"></i>
                        <p>Topup & Client Canvasser</p>
                    </a>
                </li>           --}}
                

                @if($isAdmin || $isPH)
                    <li class="nav-item {{ request()->routeIs('mitra-sbp') || request()->routeIs('report-campaign-sbp') || request()->routeIs('report-agency-advertising') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('mitra-sbp') || request()->routeIs('report-campaign-sbp') || request()->routeIs('report-agency-advertising') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-network-wired" style="color:#28a745;"></i>
                                <p>
                                    Mitra SBP
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                    <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('mitra-sbp') }}"
                            class="nav-link waves-effect {{ request()->routeIs('mitra-sbp') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-chart-column" style="color:rgb(173, 252, 157);"></i>
                            <p>Performance Report</p>
                        </a>
                    </li> 
                    <li class="nav-item">
                        <a href="{{ route('report-campaign-sbp') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-campaign-sbp') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-bullhorn" style="color:#ffc107;"></i>
                            <p>Report Campaign Advertising</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('report-agency-advertising') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-agency-advertising') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-bullhorn" style="color:#fd7e14;"></i>
                            <p>Report Agency Advertising</p>
                        </a>
                    </li>
                    </ul>
            </li>
        @endif
                {{-- <li class="nav-item">
                    <a href="{{ route('logbook.index') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logbook.index') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-book" style="color:rgb(118, 129, 255);"></i>
                        <p>New Logbook</p>
                    </a>
                </li> --}}
                {{-- ===== Menu khusus ADMIN ===== --}}
                {{-- @if($isAdmin)
                <li class="nav-header">Upload File</li>
                <li class="nav-item">
                    <a href="{{ route('admin.upload') }}"
                        class="nav-link waves-effect {{ request()->routeIs('admin.upload') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-bullseye" style="color:rgb(240,236,1);"></i>
                        <p>Revenue & Program</p>
                    </a>
                </li>
                @endif --}}
                @if($isAdmin || $isTreg || $isTsel || $isCanv || $isPH)
                <li class="nav-item {{ (request()->routeIs('panenpoin.*') || request()->routeIs('admin.monitoring.canvasser_voucher')) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link waves-effect {{ (request()->routeIs('panenpoin.*') || request()->routeIs('admin.monitoring.canvasser_voucher')) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-layer-group"></i>
                        <p>
                            Program Campaign
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">

                        <!-- Program Panen Poin -->
                        <li class="nav-item {{ request()->routeIs('panenpoin.*') ? 'menu-open' : '' }} ml-2">
                            <a href="#" class="nav-link {{ request()->routeIs('panenpoin.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-coins" style="color:#28a745;"></i>
                                <p>
                                    Program Panen Poin
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('panenpoin.index') }}"
                                    class="nav-link {{ request()->routeIs('panenpoin.index') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-plus-circle nav-icon" style="color:#17a2b8;"></i>
                                        <p>Input Data</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('panenpoin.report') }}"
                                    class="nav-link {{ request()->routeIs('panenpoin.report') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-chart-bar nav-icon" style="color:#ffc107;"></i>
                                        <p>Report Poin</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('panenpoin.report-canvasser') }}"
                                    class="nav-link {{ request()->routeIs('panenpoin.report-canvasser') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-users nav-icon" style="color:#17a2b8;"></i>
                                        <p>Report Canvasser</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('panenpoin.report-ph') }}"
                                    class="nav-link {{ request()->routeIs('panenpoin.report-ph') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-user-shield nav-icon" style="color:#6f42c1;"></i>
                                        <p>Report Powerhouse</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('panenpoin.list-akun') }}"
                                    class="nav-link {{ request()->routeIs('panenpoin.list-akun') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-user-check nav-icon" style="color:#28a745;"></i>
                                        <p>Daftar Akun</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Program Canvasser Voucher -->
                        <li class="nav-item {{ request()->routeIs('admin.monitoring.canvasser_voucher') ? 'menu-open' : '' }} ml-2">
                            <a href="{{ route('admin.monitoring.canvasser_voucher') }}"
                            class="nav-link {{ request()->routeIs('admin.monitoring.canvasser_voucher') ? 'active' : '' }}"
                            style="padding-left: 20px;">
                                <i class="fas fa-ticket-alt nav-icon" style="color:#ffc107;"></i>
                                <p>Program Referral Champion</p>
                            </a>
                        </li>

                        

                    </ul>
                </li>

                @endif
                {{-- ===== PRESENSI: Hanya untuk CVSR dan Admin ===== --}}
                @if($isCanv || $isAdmin)
                <li class="nav-header">PRESENSI</li>
                <li class="nav-item {{ (request()->routeIs('presensi.*') || request()->routeIs('location-presensi.*')) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ (request()->routeIs('presensi.*') || request()->routeIs('location-presensi.*')) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-check" style="color:rgb(76, 175, 80);"></i>
                        <p>Presensi <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        @if($isCanv)
                        <li class="nav-item">
                            <a href="{{ route('presensi.index') }}"
                                class="nav-link waves-effect {{ request()->routeIs('presensi.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-clock" style="color:rgb(33, 150, 243); margin-left: 15px;"></i>
                                <p>Presensi</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('presensi.riwayat') }}"
                                class="nav-link waves-effect {{ request()->routeIs('presensi.riwayat') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-history" style="color:rgb(255, 152, 0); margin-left: 15px;"></i>
                                <p>Riwayat Presensi</p>
                            </a>
                        </li>
                        @if($isAdmin)
                        <li class="nav-item">
                            <a href="{{ route('location-presensi.index') }}"
                                class="nav-link waves-effect {{ request()->routeIs('location-presensi.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-map-marker-alt" style="color:rgb(244, 67, 54); margin-left: 15px;"></i>
                                <p>Kelola Lokasi Presensi</p>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif
                @if($isAdmin || $isTreg || $isTsel ||$isCanv || $isPH)
                <li class="nav-header">System Management</li>
                @endif
                @if($isAdmin)
                <li class="nav-item">
                    <a href="{{ route('users.page') }}"
                        class="nav-link waves-effect {{ request()->routeIs('users.page') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users-cog" style="color:#28a745;"></i>
                        <p>Manajemen Users</p>
                    </a>
                </li>
                @endif                
                @if($isAdmin || $isTsel)
                <li class="nav-item">
                    <a href="{{ route('loglogin') }}"
                        class="nav-link waves-effect {{ request()->routeIs('loglogin') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-history" style="color:#17a2b8;"></i>
                        <p>Log Login</p>
                    </a>
                </li>
                @endif
                
            
                
                @if($isAdmin || $isTreg || $isTsel || $isCanv || $isPH)
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                @endif



                {{-- ===== Logout untuk semua role yang ditangani di atas ===== --}}
                @if($isAdmin || $isTreg || $isTsel || $isCanv || $isPH)
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
