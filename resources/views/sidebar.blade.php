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
                $isTcd = $user->role === 'TCD';
                $isInternal = $user->role === 'Internal';
                $isB2b = $user->role === 'b2b';
                $isMaxim = $user->role === 'Maxim';
                $isAutomatech = $user->role === 'Automatech';
                $isCdsi = $user->role === 'CDSI';
                $isMpcc = $user->role === 'MPCC';
                @endphp
                @if($isAdmin && $user->email === 'admin@telkomsel.co.id')
                <span class="badge badge-warning">SUPER ADMIN</span>
                @elseif($isAdmin)
                <span class="badge badge-warning">ADMIN</span>
                @elseif($isTsel)
                <span class="badge badge-success">TSEL</span>
                @elseif($isPH)
                <span class="badge badge-info">POWERHOUSE</span>
                @elseif($isTcd)
                <span class="badge badge-secondary">TCD</span>
                @elseif($isInternal)
                <span class="badge badge-success">Internal</span>
                @elseif($isB2b)
                <span class="badge badge-primary">B2B</span>
                @elseif($isCdsi)
                <span class="badge badge-danger">CDSI</span>
                @elseif($isMaxim)
                <span class="badge badge-warning">MAXIM</span>
                @elseif($isMpcc)
                <span class="badge badge-info">MPCC</span>
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
                @if($isTcd)
                <li class="nav-header">AGENCY ADVERTISING</li>
                <li class="nav-item {{ request()->routeIs('report-agency-advertising') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('report-agency-advertising') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-network-wired" style="color:#f8a912;"></i>
                        <p>
                            Agency Advertising
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('report-agency-advertising') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-agency-advertising') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-bullhorn" style="color:#fd7e14;"></i>
                                <p>Report Agency Advertising</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">System Management</li>
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @elseif($isB2b)
                <li class="nav-header">PROGRAM CAMPAIGN</li>
                <li class="nav-item {{ request()->routeIs('amlevelup.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('amlevelup.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-medal" style="color:#f39c12;"></i>
                        <p>
                            Program AM Level UP
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('amlevelup.summary') }}"
                                class="nav-link waves-effect {{ request()->routeIs('amlevelup.summary') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="fas fa-chart-bar nav-icon" style="color:#ffc107;"></i>
                                <p>Report Poin</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">System Management</li>
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @elseif($isInternal)
                <li class="nav-header">Internal</li>
                <li class="nav-item {{ request()->routeIs('mitra-sbp') || request()->routeIs('report-campaign-sbp') || request()->routeIs('report-saldo-sbp') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('mitra-sbp') || request()->routeIs('report-campaign-sbp') || request()->routeIs('report-saldo-sbp') ? 'active' : '' }}">
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
                                <p>Report Campaign</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('report-saldo-sbp') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-saldo-sbp') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-piggy-bank" style="color:#ffc1cc;"></i>
                                <p>Report Saldo</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">System Management</li>
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @elseif($isMaxim)
                <li class="nav-item {{ request()->routeIs('report-maxim') || request()->routeIs('report-saldo-maxim') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('report-maxim') || request()->routeIs('report-saldo-maxim') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-car" style="color:#fbff00;"></i>
                        <p>
                            Maxim
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('report-maxim') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-maxim') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-bullhorn" style="color:#f59e0b;"></i>
                                <p>Report Maxim</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('report-saldo-maxim') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-saldo-maxim') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-piggy-bank" style="color:#ffc1cc;"></i>
                                <p>Report Saldo Maxim</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">System Management</li>
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @elseif($isMpcc)
                <li class="nav-header">ALL DASHBOARD</li>
                <li class="nav-item has-treeview {{ (request()->routeIs('mpcc.report') || request()->routeIs('mpcc.report.area-branch*') || request()->routeIs('mpcc.report.pilot-city')) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ (request()->routeIs('mpcc.report') || request()->routeIs('mpcc.report.area-branch*') || request()->routeIs('mpcc.report.pilot-city')) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-headset" style="color:#17a2b8;"></i>
                        <p>
                            MPCC
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('mpcc.report') }}" class="nav-link waves-effect {{ request()->routeIs('mpcc.report') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fas fa-chart-bar" style="color:#ffc107;"></i>
                                <p>MPCC Report</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('mpcc.report.area-branch') }}" class="nav-link waves-effect {{ request()->routeIs('mpcc.report.area-branch*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fas fa-sitemap" style="color:#20c997;"></i>
                                <p>MPCC Area Branch</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('mpcc.report.pilot-city') }}" class="nav-link waves-effect {{ request()->routeIs('mpcc.report.pilot-city') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fas fa-table" style="color:#6f42c1;"></i>
                                <p>MPCC Pilot City</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="{{ route('leads-master.index') }}" class="nav-link waves-effect {{ request()->routeIs('leads-master.index') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-star" style="color:rgb(240,236,1);"></i>
                        <p>Data Leads & Akun</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('leads-master.create') }}" class="nav-link waves-effect {{ request()->routeIs('leads-master.create') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-user-pen" style="color:rgb(1, 240, 172);"></i>
                        <p>New Leads</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('leads-master.create-existing') }}" class="nav-link waves-effect {{ request()->routeIs('leads-master.create-existing') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-user-tie" style="color:rgb(143, 142, 142);"></i>
                        <p>New/Eksisting Akun</p>
                    </a>
                </li>

                <li class="nav-header">System Management</li>
                <li class="nav-item">
                    <a href="{{ url('change-password') }}" class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}" class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @elseif($isAutomatech)
                <li class="nav-item {{ request()->routeIs('report-automatech') || request()->routeIs('report-saldo-automatech') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('report-automatech') || request()->routeIs('report-saldo-automatech') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-industry" style="color:#ff6b35;"></i>
                        <p>
                            Automatech
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('report-automatech') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-automatech') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-bullhorn" style="color:#f59e0b;"></i>
                                <p>Report Automatech</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('report-saldo-automatech') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-saldo-automatech') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-piggy-bank" style="color:#ffc1cc;"></i>
                                <p>Report Saldo Automatech</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">System Management</li>
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @elseif($isCdsi)
                <li class="nav-item {{ request()->routeIs('report-cdsi*') || request()->routeIs('report-cdsi-dormant*') || request()->routeIs('report-cdsi-province*') || request()->routeIs('cdsi.referrals*') || request()->routeIs('cdsi.referral-topup-channel*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('report-cdsi*') || request()->routeIs('report-cdsi-dormant*') || request()->routeIs('report-cdsi-province*') || request()->routeIs('cdsi.referrals*') || request()->routeIs('cdsi.referral-topup-channel*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-broadcast-tower" style="color:#ef4444;"></i>
                        <p>
                            CDSI
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('cdsi.referral-topup-channel') }}"
                                class="nav-link waves-effect {{ request()->routeIs('cdsi.referral-topup-channel*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-table" style="color:#fb7185;"></i>
                                <p>TopUp Referral CDSI</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('report-cdsi') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-cdsi') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-chart-column" style="color:#f59e0b;"></i>
                                <p>Report CDSI</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('cdsi.referrals') }}"
                                class="nav-link waves-effect {{ request()->routeIs('cdsi.referrals*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-user-plus" style="color:#38bdf8;"></i>
                                <p>Referral CDSI</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('report-cdsi-dormant') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-cdsi-dormant*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-bed" style="color:#6f42c1;"></i>
                                <p>Data Dormant</p>
                            </a>
                        </li>
                        @if(false)
                        <li class="nav-item">
                            <a href="{{ route('report-cdsi-province') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-cdsi-province*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-map-location-dot" style="color:#22c55e;"></i>
                                <p>Top Up Active</p>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                <li class="nav-header">System Management</li>
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @else

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
                <li class="nav-item">
                    <a href="{{ route('tips-sales') }}"
                        class="nav-link waves-effect {{ request()->routeIs('tips-sales') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-lightbulb" style="color:rgb(255, 193, 7);"></i>
                        <p>Tips Sales</p>
                    </a>
                </li>
                <li class="nav-item has-treeview {{ (request()->routeIs('mpcc.report') || request()->routeIs('mpcc.report.area-branch*') || request()->routeIs('mpcc.report.pilot-city')) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ (request()->routeIs('mpcc.report') || request()->routeIs('mpcc.report.area-branch*') || request()->routeIs('mpcc.report.pilot-city')) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-headset" style="color:#17a2b8;"></i>
                        <p>
                            MPCC
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('mpcc.report') }}" class="nav-link waves-effect {{ request()->routeIs('mpcc.report') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fas fa-chart-bar" style="color:#ffc107;"></i>
                                <p>MPCC Report</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('mpcc.report.area-branch') }}" class="nav-link waves-effect {{ request()->routeIs('mpcc.report.area-branch*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fas fa-sitemap" style="color:#20c997;"></i>
                                <p>MPCC Area Branch</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('mpcc.report.pilot-city') }}" class="nav-link waves-effect {{ request()->routeIs('mpcc.report.pilot-city') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fas fa-table" style="color:#6f42c1;"></i>
                                <p>MPCC Pilot City</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                @if($isAdmin || $isTsel || $isPH)
                <li class="nav-item has-treeview 
                    {{ (request()->routeIs('region-target') || request()->routeIs('admin.monitoring.powerhouse_referral') || request()->routeIs('admin.monitoring.powerhouse_semester')) ? 'menu-open' : '' }}">
                    
                    <a href="#" class="nav-link 
                        {{ (request()->routeIs('region-target') || request()->routeIs('admin.monitoring.powerhouse_referral') || request()->routeIs('admin.monitoring.powerhouse_semester')) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-house-chimney-user"></i>
                        <p>
                            Powerhouse
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">
                        @if($isAdmin)
                        <li class="nav-item">
                            <a href="{{ route('region-target') }}"
                            class="nav-link {{ request()->routeIs('region-target') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-chart-line" style="color:rgb(240, 37, 1);"></i>
                                <p>Report Powerhouse</p>
                            </a>
                        </li>
                        @endif

                        <!-- Powerhouse Referral -->
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.powerhouse_referral') }}"
                            class="nav-link {{ request()->routeIs('admin.monitoring.powerhouse_referral') ? 'active' : '' }}"  style="padding-left: 45px;">
                                <i class="nav-icon fas fa-star" style="color:#ffc107;"></i>
                                <p>PowerHouse Referral</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.monitoring.powerhouse_semester') }}"
                            class="nav-link {{ request()->routeIs('admin.monitoring.powerhouse_semester') ? 'active' : '' }}"  style="padding-left: 45px;">
                                <i class="nav-icon fas fa-calendar-alt" style="color:#17a2b8;"></i>
                                <p>PowerHouse Semester</p>
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
                @if($isAdmin)
                <li class="nav-item">
                    <a href="{{ route('leads-master.create-enterprise') }}"
                        class="nav-link waves-effect {{ request()->routeIs('leads-master.create-enterprise') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-building" style="color:rgb(245, 158, 11);"></i>
                        <p>Enterprise Akun</p>
                    </a>
                </li>
                @endif
                @if($isAdmin || $isCanv || $isPH || $isMpcc)
                <li class="nav-item">
                    <a href="{{ route('faq-l0') }}"
                        class="nav-link waves-effect {{ request()->routeIs('faq-l0') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-circle-question" style="color:#20c997;"></i>
                        <p>FAQ L0</p>
                    </a>
                </li>
                @endif
                @if(!$isCanv && !$isMpcc)
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
                            class="nav-link {{ request()->routeIs('logbook.index') ? 'active' : '' }}"  style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-book" style="color:rgb(90,90,250);"></i>
                                <p>Logbook Monthly</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('logbook-daily.index') }}"
                            class="nav-link {{ request()->routeIs('logbook-daily.index') ? 'active' : '' }}"  style="padding-left: 45px;">
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
                @if($isAdmin || $isTcd)
                <li class="nav-item {{ request()->routeIs('report-agency-advertising') || request()->routeIs('report-saldo-advertising') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('report-agency-advertising') || request()->routeIs('report-saldo-advertising') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-network-wired" style="color:#f8a912;"></i>
                                <p>
                                    Agency Advertising
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                    <ul class="nav nav-treeview">
                    
                    <li class="nav-item">
                        <a href="{{ route('report-agency-advertising') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-agency-advertising') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-bullhorn" style="color:#fd7e14;"></i>
                            <p>Report Agency Advertising</p>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('report-saldo-advertising') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-saldo-advertising') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-piggy-bank" style="color:#ffc1cc;"></i>
                            <p>Report Saldo Agency Advertising</p>
                        </a>
                    </li>
                    </ul>
                </li>
                @endif
                @if($isAdmin || $isMaxim)
                <li class="nav-item {{ request()->routeIs('report-maxim') || request()->routeIs('report-saldo-maxim') || request()->routeIs('admin.upload.maxim-report') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('report-maxim') || request()->routeIs('report-saldo-maxim') || request()->routeIs('admin.upload.maxim-report') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-car" style="color:#fbff00;"></i>
                                <p>
                                    Maxim
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                    <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('report-maxim') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-maxim') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-bullhorn" style="color:#f59e0b;"></i>
                            <p>Report Maxim</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('report-saldo-maxim') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-saldo-maxim') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-piggy-bank" style="color:#ffc1cc;"></i>
                            <p>Report Saldo Maxim</p>
                        </a>
                    </li>
                    @if($isAdmin)
                    <li class="nav-item">
                        <a href="{{ route('admin.upload.maxim-report') }}"
                            class="nav-link waves-effect {{ request()->routeIs('admin.upload.maxim-report') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-file-arrow-up" style="color:#7dd3fc;"></i>
                            <p>Upload Report Maxim</p>
                        </a>
                    </li>
                    @endif
                    </ul>
                </li>
                @endif
                @if($isAdmin || $isAutomatech)
                <li class="nav-item {{ request()->routeIs('report-automatech') || request()->routeIs('report-saldo-automatech') || request()->routeIs('admin.upload.automatech-report') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('report-automatech') || request()->routeIs('report-saldo-automatech') || request()->routeIs('admin.upload.automatech-report') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-industry" style="color:#ff6b35;"></i>
                                <p>
                                    Automatech
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                    <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{ route('report-automatech') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-automatech') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-bullhorn" style="color:#f59e0b;"></i>
                            <p>Report Automatech</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('report-saldo-automatech') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-saldo-automatech') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-piggy-bank" style="color:#ffc1cc;"></i>
                            <p>Report Saldo Automatech</p>
                        </a>
                    </li>
                    @if($isAdmin)
                    <li class="nav-item">
                        <a href="{{ route('admin.upload.automatech-report') }}"
                            class="nav-link waves-effect {{ request()->routeIs('admin.upload.automatech-report') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-file-arrow-up" style="color:#7dd3fc;"></i>
                            <p>Upload Report Automatech</p>
                        </a>
                    </li>
                    @endif
                    </ul>
                </li>
                @if($isAdmin)
                <li class="nav-item {{ request()->routeIs('report-cdsi*') || request()->routeIs('report-cdsi-dormant*') || request()->routeIs('report-cdsi-province*') || request()->routeIs('admin.upload.cdsi-report') || request()->routeIs('cdsi.referrals*') || request()->routeIs('cdsi.referral-topup-channel*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('report-cdsi*') || request()->routeIs('report-cdsi-dormant*') || request()->routeIs('report-cdsi-province*') || request()->routeIs('admin.upload.cdsi-report') || request()->routeIs('cdsi.referrals*') || request()->routeIs('cdsi.referral-topup-channel*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-broadcast-tower" style="color:#ef4444;"></i>
                        <p>
                            CDSI
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('cdsi.referral-topup-channel') }}"
                                class="nav-link waves-effect {{ request()->routeIs('cdsi.referral-topup-channel*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-table" style="color:#fb7185;"></i>
                                <p>TopUp Referral CDSI</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('report-cdsi') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-cdsi') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-chart-column" style="color:#f59e0b;"></i>
                                <p>Report CDSI</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('cdsi.referrals') }}"
                                class="nav-link waves-effect {{ request()->routeIs('cdsi.referrals*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-user-plus" style="color:#38bdf8;"></i>
                                <p>Referral CDSI</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('report-cdsi-dormant') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-cdsi-dormant*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-bed" style="color:#6f42c1;"></i>
                                <p>Data Dormant</p>
                            </a>
                        </li>
                        @if(false)
                        <li class="nav-item">
                            <a href="{{ route('report-cdsi-province') }}"
                                class="nav-link waves-effect {{ request()->routeIs('report-cdsi-province*') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-map-location-dot" style="color:#22c55e;"></i>
                                <p>Top Up Active</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('admin.upload.cdsi-report') }}"
                                class="nav-link waves-effect {{ request()->routeIs('admin.upload.cdsi-report') ? 'active' : '' }}" style="padding-left: 45px;">
                                <i class="nav-icon fa-solid fa-file-arrow-up" style="color:#7dd3fc;"></i>
                                <p>Upload Report CDSI</p>
                            </a>
                        </li>
                    </ul>
                </li>

                
                @endif
                @endif
                @if($isAdmin || $isPH)
                    <li class="nav-item {{ request()->routeIs('mitra-sbp') || request()->routeIs('report-campaign-sbp') || request()->routeIs('report-saldo-sbp') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('mitra-sbp') || request()->routeIs('report-campaign-sbp') || request()->routeIs('report-saldo-sbp') ? 'active' : '' }}">
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
                            <p>Report Campaign</p>
                        </a>
                    </li> 
                    <li class="nav-item">
                        <a href="{{ route('report-saldo-sbp') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-saldo-sbp') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-piggy-bank" style="color:#ffc1cc;"></i>
                            <p>Report Saldo</p>
                        </a>
                        </li>
                    </ul>
                </li>
                @endif
                @if($isAdmin)
                <li class="nav-item {{ request()->routeIs('report-avalon-kemang-bogor') || request()->routeIs('report-saldo-avalon-kemang-bogor') || request()->routeIs('admin.upload.avalon-kemang-bogor-report') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('report-avalon-kemang-bogor') || request()->routeIs('report-saldo-avalon-kemang-bogor') || request()->routeIs('admin.upload.avalon-kemang-bogor-report') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-city" style="color:#8e44ad;"></i>
                                <p>
                                    Avalon Kemang Bogor
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('report-avalon-kemang-bogor') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-avalon-kemang-bogor') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-bullhorn" style="color:#f59e0b;"></i>
                            <p>Report Avalon Kemang Bogor</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('report-saldo-avalon-kemang-bogor') }}"
                            class="nav-link waves-effect {{ request()->routeIs('report-saldo-avalon-kemang-bogor') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-piggy-bank" style="color:#ffc1cc;"></i>
                            <p>Report Saldo Avalon</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.upload.avalon-kemang-bogor-report') }}"
                            class="nav-link waves-effect {{ request()->routeIs('admin.upload.avalon-kemang-bogor-report') ? 'active' : '' }}" style="padding-left: 45px;">
                            <i class="nav-icon fa-solid fa-file-arrow-up" style="color:#7dd3fc;"></i>
                            <p>Upload Report Avalon</p>
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
                @if($isAdmin || $isTreg || $isTsel || $isCanv || $isPH || $isMpcc)
                <li class="nav-item {{ (request()->routeIs('panenpoinv2.*') || request()->routeIs('amlevelup.*') || request()->routeIs('admin.monitoring.canvasser_voucher')) ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link waves-effect {{ (request()->routeIs('panenpoinv2.*') || request()->routeIs('amlevelup.*') || request()->routeIs('admin.monitoring.canvasser_voucher')) ? 'active' : '' }}">
                        <i class="nav-icon fas fa-layer-group"></i>
                        <p>
                            Program Campaign
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>

                    <ul class="nav nav-treeview">


                        <!-- Program Panen Poin V2 -->
                        <li class="nav-item {{ request()->routeIs('panenpoinv2.*') ? 'menu-open' : '' }} ml-2">
                            <a href="#" class="nav-link {{ request()->routeIs('panenpoinv2.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-coins" style="color:#20c997;"></i>
                                <p>
                                    Program Panen Poin V2
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('panenpoinv2.index') }}"
                                    class="nav-link {{ request()->routeIs('panenpoinv2.index') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-plus-circle nav-icon" style="color:#17a2b8;"></i>
                                        <p>Input Data</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('panenpoinv2.report') }}"
                                    class="nav-link {{ request()->routeIs('panenpoinv2.report') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-chart-bar nav-icon" style="color:#ffc107;"></i>
                                        <p>Report Poin</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('panenpoinv2.report-canvasser') }}"
                                    class="nav-link {{ request()->routeIs('panenpoinv2.report-canvasser') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-users nav-icon" style="color:#17a2b8;"></i>
                                        <p>Report Canvasser</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('panenpoinv2.report-ph') }}"
                                    class="nav-link {{ request()->routeIs('panenpoinv2.report-ph') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-user-shield nav-icon" style="color:#6f42c1;"></i>
                                        <p>Report Powerhouse</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('panenpoinv2.list-akun') }}"
                                    class="nav-link {{ request()->routeIs('panenpoinv2.list-akun') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-user-check nav-icon" style="color:#28a745;"></i>
                                        <p>List Akun</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        @if($isAdmin)
                        <!-- Program AM Level UP -->
                        <li class="nav-item {{ request()->routeIs('amlevelup.*') ? 'menu-open' : '' }} ml-2">
                            <a href="#" class="nav-link {{ request()->routeIs('amlevelup.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-medal" style="color:#f39c12;"></i>
                                <p>
                                    Program AM Level UP
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">
                                {{-- <li class="nav-item">
                                    <a href="{{ route('amlevelup.index') }}"
                                    class="nav-link {{ request()->routeIs('amlevelup.index') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-plus-circle nav-icon" style="color:#17a2b8;"></i>
                                        <p>Input Data</p>
                                    </a>
                                </li> --}}

                                <li class="nav-item">
                                    <a href="{{ route('amlevelup.summary') }}"
                                    class="nav-link {{ request()->routeIs('amlevelup.summary') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-chart-bar nav-icon" style="color:#ffc107;"></i>
                                        <p>Report Poin</p>
                                    </a>
                                </li>

                                {{-- <li class="nav-item">
                                    <a href="{{ route('amlevelup.report-canvasser') }}"
                                    class="nav-link {{ request()->routeIs('amlevelup.report-canvasser') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-users nav-icon" style="color:#17a2b8;"></i>
                                        <p>Report Canvasser</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ route('amlevelup.report-ph') }}"
                                    class="nav-link {{ request()->routeIs('amlevelup.report-ph') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-user-shield nav-icon" style="color:#6f42c1;"></i>
                                        <p>Report Powerhouse</p>
                                    </a>
                                </li> --}}

                                <li class="nav-item">
                                    <a href="{{ route('amlevelup.clients') }}"
                                    class="nav-link {{ request()->routeIs('amlevelup.clients') ? 'active' : '' }}"
                                    style="padding-left: 45px;">
                                        <i class="fas fa-user-check nav-icon" style="color:#28a745;"></i>
                                        <p>Daftar Client</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif

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
                @if($isAdmin || $isCanv || $isPH)
                <li class="nav-header">EVENT APRIL</li>
                <li class="nav-item">
                    <a href="{{ route('logbook-event.index') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logbook-event.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-calendar-alt" style="color:#20c997;"></i>
                        <p>Logbook Event April 2026</p>
                    </a>
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
                @if($isAdmin)
                <li class="nav-header">Configuration</li>
                <li class="nav-item">
                    <a href="{{ route('configuration.mitra-sbp.index') }}"
                        class="nav-link waves-effect {{ request()->routeIs('configuration.mitra-sbp.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-database" style="color:#17a2b8;"></i>
                        <p>Configuration Mitra SBP</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('report-balance-top-up') }}"
                        class="nav-link waves-effect {{ request()->routeIs('report-balance-top-up') ? 'active' : '' }}">
                        <i class="nav-icon fa-solid fa-wallet" style="color:#79c0ff;"></i>
                        <p>Report Balance Top Up</p>
                    </a>
                </li>
                @endif
                @if($isAdmin)
                <li class="nav-header">System Management</li>
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
                
            
                
                @if($isAdmin || $isTreg || $isTsel || $isCanv || $isPH || $isMpcc)
                <li class="nav-item">
                    <a href="{{ url('change-password') }}"
                        class="nav-link waves-effect {{ request()->routeIs('change-password') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-key" style="color:rgb(173, 176, 86);"></i>
                        <p>Change Password</p>
                    </a>
                </li>
                @endif



                {{-- ===== Logout untuk semua role yang ditangani di atas ===== --}}
                @if($isAdmin || $isTreg || $isTsel || $isCanv || $isPH || $isMaxim || $isAutomatech || $isMpcc)
                <li class="nav-header">LOGOUT</li>
                <li class="nav-item">
                    <a href="{{ url('logout') }}"
                        class="nav-link waves-effect {{ request()->routeIs('logout') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sign-out-alt" style="color:rgb(239,21,21);"></i>
                        <p>Logout</p>
                    </a>
                </li>
                @endif
                @endif
            </ul>
        </nav>
    </div>
</aside>













