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
                $isCanv = $user->role === 'Canvasser';
                @endphp

                @if($isAdmin && $user->email === 'admin@telkomsel.co.id')
                <span class="badge badge-warning">SUPER ADMIN</span>
                @elseif($user->role === 'Admin')
                <span class="badge badge-warning">ADMIN</span>
                @elseif($user->role === 'Tsel')
                <span class="badge badge-success">TSEL</span>
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
                @if($isAdmin || $isTsel)
                <li class="nav-header">ALL DASHBOARD</li>
                <li class="nav-item">
                    <a href="{{ route('admin.home') }}"
                        class="nav-link waves-effect {{ request()->routeIs('admin.home') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-bullseye" style="color:rgb(240, 236, 1);"></i>
                        <p>Home Dashboard</p>
                    </a>
                </li>

                <li class="nav-header">All Program</li>
                <li class="nav-item">
                    <a href="{{ route('admin.monitoring.padi_umkm') }}"
                        class="nav-link waves-effect {{ request()->routeIs('admin.monitoring.padi_umkm') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-shop" style="color:#4b66ff;"></i>
                        <p>Padi UMKM</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.monitoring.event_sponsorship') }}"
                        class="nav-link waves-effect {{ request()->routeIs('admin.monitoring.event_sponsorship') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-handshake" style="color:#ff5733;"></i>
                        <p>Event Sponsorship</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs([
                        'admin.monitoring.creator_partner',
                        'admin.monitoring.rekruter_kol_buzzer',
                        'admin.monitoring.rekruter_kol_influencer',
                        'admin.monitoring.area_marcom'
                    ]) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs([
                            'admin.monitoring.creator_partner',
                            'admin.monitoring.rekruter_kol_buzzer',
                            'admin.monitoring.rekruter_kol_influencer',
                            'admin.monitoring.area_marcom'
                        ]) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Monitoring KOL <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.creator_partner') }}"
                                class="nav-link {{ request()->routeIs('admin.monitoring.creator_partner') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fas fa-star nav-icon" style="color:#ffc107;"></i>
                                <p>Creator Partner</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.rekruter_kol_buzzer') }}"
                                class="nav-link {{ request()->routeIs('admin.monitoring.rekruter_kol_buzzer') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fas fa-bullhorn nav-icon" style="color:#e74c3c;"></i>
                                <p>Rekruter KOL Buzzer</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.rekruter_kol_influencer') }}"
                                class="nav-link {{ request()->routeIs('admin.monitoring.rekruter_kol_influencer') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fas fa-crown nav-icon" style="color:#9b59b6;"></i>
                                <p>Rekruter KOL Influencer</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.area_marcom') }}"
                                class="nav-link {{ request()->routeIs('admin.monitoring.area_marcom') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fas fa-map-marked-alt nav-icon" style="color:#3498db;"></i>
                                <p>Area Marcom KOL</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.monitoring.simpati_tiktok') }}"
                        class="nav-link waves-effect {{ request()->routeIs('admin.monitoring.simpati_tiktok') ? 'active' : '' }}">
                        <i class="nav-icon fa-brands fa-tiktok" style="color:#fe0404;"></i>
                        <p>Simpati Tiktok</p>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs([
                        'admin.monitoring.referral_tele_am',
                        'admin.monitoring.referral_canvasser'
                    ]) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs([
                            'admin.monitoring.referral_tele_am',
                            'admin.monitoring.referral_canvasser'
                        ]) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-users" style="color:#42f554;"></i>
                        <p>Referral Champion <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.referral_tele_am') }}"
                                class="nav-link {{ request()->routeIs('admin.monitoring.referral_tele_am') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fa fa-user-check nav-icon"></i>
                                <p>Tele AM</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.referral_canvasser') }}"
                                class="nav-link {{ request()->routeIs('admin.monitoring.referral_canvasser') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fa fa-user-check nav-icon"></i>
                                <p>Canvasser</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="{{ route('admin.monitoring.sultam_racing') }}"
                        class="nav-link waves-effect {{ request()->routeIs('admin.monitoring.sultam_racing') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-car" style="color:#f39c12;"></i>
                        <p>Sultam Racing</p>
                    </a>
                </li>

                <li class="nav-item {{ (request()->routeIs('admin.voucher') || request()->routeIs('admin.claim.voucher')) ? 'menu-open' : '' }}">
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
                </li>
                @endif

                {{-- ===== Menu untuk ADMIN dan TREG ===== --}}
                @if($isTreg || $isAdmin)
                <li class="nav-item {{ request()->routeIs([
                        'monitoring_akuisisi_treg',
                        'race_summary_treg'
                    ]) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs([
                            'monitoring_akuisisi_treg',
                            'race_summary_treg'
                        ]) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line" style="color:#dc3545;"></i>
                        <p>AdsVantage Race <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('monitoring_akuisisi_treg') }}"
                                class="nav-link {{ request()->routeIs('monitoring_akuisisi_treg') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fa fa-list nav-icon"></i>
                                <p>Detail Akuisisi</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('race_summary_treg') }}"
                                class="nav-link {{ request()->routeIs('race_summary_treg') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fa fa-trophy nav-icon"></i>
                                <p>Race Summary</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif

                <li class="nav-header">Report</li>
                <li class="nav-item">
                    <a href="{{ route('leads-master.index') }}"
                        class="nav-link waves-effect {{ request()->routeIs('leads-master.index') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-star" style="color:rgb(240,236,1);"></i>
                        <p>Lead Master</p>
                    </a>
                </li>                
                <li class="nav-item">
                    <a href="{{ route('topup-canvasser') }}"
                        class="nav-link waves-effect {{ request()->routeIs('topup-canvasser') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-star" style="color:rgb(240,236,1);"></i>
                        <p>Topup & Client Canvasser</p>
                    </a>
                </li>          
                <li class="nav-item">
                    <a href="{{ route('region-target') }}"
                        class="nav-link waves-effect {{ request()->routeIs('region-target') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-star" style="color:rgb(240,236,1);"></i>
                        <p>Region Target Topup</p>
                    </a>
                </li>   
                {{-- <li class="nav-item">
                    <a href="{{ route('logbook.index') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logbook.index') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-book" style="color:rgb(118, 129, 255);"></i>
                        <p>New Logbook</p>
                    </a>
                </li> --}}
                {{-- ===== Menu khusus ADMIN ===== --}}
                @if($isAdmin)
                <li class="nav-header">Upload File</li>
                <li class="nav-item">
                    <a href="{{ route('admin.upload') }}"
                        class="nav-link waves-effect {{ request()->routeIs('admin.upload') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-bullseye" style="color:rgb(240,236,1);"></i>
                        <p>Revenue & Program</p>
                    </a>
                </li>
                @endif

                @if($isAdmin || $isTreg || $isTsel || $isCanv)
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
                @if($isAdmin || $isTreg || $isTsel || $isCanv)
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                @endif



                {{-- ===== Logout untuk semua role yang ditangani di atas ===== --}}
                @if($isAdmin || $isTreg || $isTsel || $isCanv)
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