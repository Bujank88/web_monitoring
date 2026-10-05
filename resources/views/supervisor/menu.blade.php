<li class="nav-header">ALL DASHBOARD</li>
@foreach([
    ['daily.topup.channel', 'Daily Top Up Channel', 'fa-chart-line', '#ff9f40'],
    ['admin.home', 'Report Canvasser', 'fa-table', '#ffffff'],
    ['tips-sales', 'Tips Sales', 'fa-lightbulb', '#ffc107'],
    ['supervisor.leads', 'Data Leads & Akun', 'fa-star', '#f0ec01'],
    ['faq-l0', 'FAQ L0', 'fa-circle-question', '#20c997'],
] as [$target, $label, $icon, $color])
<li class="nav-item"><a href="{{ route($target) }}" class="nav-link {{ request()->routeIs($target) ? 'active' : '' }}"><i class="nav-icon fas {{ $icon }}" style="color:{{ $color }};"></i><p>{{ $label }}</p></a></li>
@endforeach
<li class="nav-item {{ request()->routeIs('supervisor.logbook') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('supervisor.logbook') ? 'active' : '' }}"><i class="nav-icon fas fa-book" style="color:#d4ed31;"></i><p>Logbook<i class="right fas fa-angle-left"></i></p></a>
    <ul class="nav nav-treeview">
    @foreach(['monthly' => 'Logbook Monthly', 'daily' => 'Logbook Daily'] as $period => $label)
        <li class="nav-item"><a href="{{ route('supervisor.logbook', $period) }}" class="nav-link {{ request()->routeIs('supervisor.logbook') && request()->route('period') === $period ? 'active' : '' }}"><i class="nav-icon far fa-circle" style="color:#d4ed31;"></i><p>{{ $label }}</p></a></li>
    @endforeach
    </ul>
</li>
<li class="nav-item {{ request()->routeIs('panenpoinv3.*', 'panenpoinv4.*', 'admin.monitoring.canvasser_voucher') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link"><i class="nav-icon fas fa-layer-group" style="color:#b9cd93;"></i><p>Program Campaign<i class="right fas fa-angle-left"></i></p></a>
    <ul class="nav nav-treeview">
    @foreach([4, 3] as $version)
        <li class="nav-item {{ request()->routeIs('panenpoinv'.$version.'.*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link"><i class="nav-icon fas fa-coins" style="color:#0ea5e9;"></i><p>Panen Poin V{{ $version }}<i class="right fas fa-angle-left"></i></p></a>
            <ul class="nav nav-treeview">
            @foreach(['report' => 'Report Poin', 'report-canvasser' => 'Report Canvasser', 'list-akun' => 'List Akun'] as $suffix => $label)
                <li class="nav-item"><a href="{{ route('panenpoinv'.$version.'.'.$suffix) }}" class="nav-link {{ request()->routeIs('panenpoinv'.$version.'.'.$suffix) ? 'active' : '' }}"><i class="nav-icon fas {{ $suffix === 'report' ? 'fa-chart-bar' : ($suffix === 'list-akun' ? 'fa-user-check' : 'fa-users') }}" style="color:{{ $suffix === 'report' ? '#ffc107' : ($suffix === 'list-akun' ? '#28a745' : '#17a2b8') }};"></i><p>{{ $label }}</p></a></li>
            @endforeach
            </ul>
        </li>
    @endforeach
        <li class="nav-item"><a href="{{ route('admin.monitoring.canvasser_voucher') }}" class="nav-link {{ request()->routeIs('admin.monitoring.canvasser_voucher') ? 'active' : '' }}"><i class="nav-icon fas fa-ticket-alt" style="color:#ffc107;"></i><p>Program Referral Champion</p></a></li>
    </ul>
</li>
<li class="nav-header">FBM</li>
<li class="nav-item"><a href="{{ route('supervisor.sof.create') }}" class="nav-link {{ request()->routeIs('supervisor.sof.create') ? 'active' : '' }}"><i class="nav-icon fas fa-file-circle-plus" style="color:#0dcaf0;"></i><p>Pengajuan SOF</p></a></li>
<li class="nav-item"><a href="{{ route('supervisor.sof') }}" class="nav-link {{ request()->routeIs('supervisor.sof') ? 'active' : '' }}"><i class="nav-icon fas fa-list" style="color:#ffc107;"></i><p>List SOF</p></a></li>
