<li class="nav-header">ALL DASHBOARD</li>
@foreach([
    ['daily.topup.channel', 'Daily Top Up Channel', 'fas fa-chart-line', '#ff9f40', 'daily.topup.channel'],
    ['admin.home', 'Report Canvasser', 'fas fa-table-cells', '#17a2b8', 'admin.home', 'topup-canvasser.*'],
    ['leads-master.index', 'Data Leads & Akun', 'fas fa-star', '#f0ec01', 'leads-master.*', 'transaction-detail*'],
] as $item)
<li class="nav-item">
    <a href="{{ route($item[0]) }}" class="nav-link {{ request()->routeIs(...array_slice($item, 4)) ? 'active' : '' }}">
        <i class="nav-icon {{ $item[2] }}" style="color:{{ $item[3] }};"></i><p>{{ $item[1] }}</p>
    </a>
</li>
@endforeach
@php($logbookActive = request()->routeIs('logbook.*', 'logbook-daily.*'))
<li class="nav-item {{ $logbookActive ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ $logbookActive ? 'active' : '' }}">
        <i class="nav-icon fas fa-book" style="color:#eafc4f;"></i><p>Logbook<i class="right fas fa-angle-left"></i></p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item"><a href="{{ route('logbook.index') }}" class="nav-link {{ request()->routeIs('logbook.*') ? 'active' : '' }}"><i class="nav-icon fas fa-book" style="color:#5a5afa;"></i><p>Logbook Monthly</p></a></li>
        <li class="nav-item"><a href="{{ route('logbook-daily.index') }}" class="nav-link {{ request()->routeIs('logbook-daily.*') ? 'active' : '' }}"><i class="nav-icon fas fa-book" style="color:#fa7d5a;"></i><p>Logbook Daily</p></a></li>
    </ul>
</li>
<li class="nav-header">System Management</li>
<li class="nav-item"><a href="{{ route('change-password') }}" class="nav-link {{ request()->routeIs('change-password') ? 'active' : '' }}"><i class="nav-icon fas fa-key" style="color:#adb056;"></i><p>Change Password</p></a></li>
<li class="nav-header">LOGOUT</li>
<li class="nav-item"><a href="{{ route('logout') }}" class="nav-link"><i class="nav-icon fas fa-sign-out-alt" style="color:#ef1515;"></i><p>Logout</p></a></li>
