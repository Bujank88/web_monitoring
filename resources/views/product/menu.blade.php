@php
    $link = static fn ($route, $label, $icon, $color = null, $matches = null) => [
        'route' => $route, 'label' => $label, 'icon' => $icon, 'color' => $color,
        'matches' => $matches ?? [$route],
    ];
    $group = static fn ($label, $icon, $color, $children) => [
        'label' => $label, 'icon' => $icon, 'color' => $color, 'children' => $children,
        'matches' => array_merge(...array_column($children, 'matches')),
    ];

    $campaignMenus = [];
    foreach (['panenpoinv4' => 'V4', 'panenpoinv3' => 'V3'] as $prefix => $version) {
        $campaignMenus[] = $group('Program Panen Poin '.$version, 'fas fa-coins', '#0ea5e9', [
            $link($prefix.'.report', 'Report Poin', 'fas fa-chart-bar', '#ffc107'),
            $link($prefix.'.report-canvasser', 'Report Canvasser', 'fas fa-users', '#17a2b8'),
            $link($prefix.'.report-ph', 'Report Powerhouse', 'fas fa-user-shield', '#6f42c1'),
            $link($prefix.'.list-akun', 'List Akun', 'fas fa-user-check', '#28a745'),
        ]);
    }
    $campaignMenus[] = $group('Program AM Level UP', 'fas fa-medal', '#f39c12', [
        $link('amlevelup.summary', 'Report Poin', 'fas fa-chart-bar', '#ffc107'),
        $link('amlevelup.clients', 'Daftar Client', 'fas fa-user-check', '#28a745'),
    ]);
    $campaignMenus[] = $link('admin.monitoring.canvasser_voucher', 'Program Referral Champion', 'fas fa-ticket-alt', '#ffc107');

    $productMenus = [
        $link('daily.topup.channel', 'Daily Top Up Channel', 'fa-solid fa-chart-line', 'rgb(255, 159, 64)'),
        $link('admin.home', 'Report Canvasser', 'fa-solid fa-table-cells', 'rgb(255, 255, 255)'),
        $link('sales-analysis.index', 'Analisa Sales', 'fas fa-chart-pie', '#38bdf8', ['sales-analysis.*']),
        $group('MPCC', 'fas fa-headset', '#17a2b8', [
            $link('mpcc.report', 'MPCC Report', 'fas fa-chart-bar', '#ffc107'),
            $link('mpcc.report.area-branch', 'MPCC Area Branch', 'fas fa-sitemap', '#20c997', ['mpcc.report.area-branch*']),
            $link('mpcc.report.pilot-city', 'MPCC Pilot City', 'fas fa-table', '#6f42c1'),
        ]),
        $group('Powerhouse', 'fas fa-house-chimney-user', null, [
            $link('region-target', 'Report Powerhouse', 'fa-solid fa-chart-line', 'rgb(240, 37, 1)'),
            $group('Leads 2M', 'fas fa-users-rectangle', '#22c55e', [
                $link('powerhouse.2m.summary', 'Summary Leads', 'fas fa-chart-pie', '#f59e0b', [
                    'powerhouse.2m.summary', 'powerhouse.2m.show',
                ]),
            ]),
            $link('powerhouse.2m.report', 'Report 2M', 'fas fa-chart-bar', '#a855f7'),
            $link('admin.monitoring.powerhouse_referral', 'PowerHouse Referral', 'fas fa-star', '#ffc107'),
            $link('admin.monitoring.powerhouse_semester', 'PowerHouse Semester', 'fas fa-calendar-alt', '#17a2b8'),
        ]),
        $link('am.referral.index', 'AM Referral', 'fas fa-user-tie', '#38bdf8', ['am.referral.*']),
        $group('Mitra SBP', 'fas fa-network-wired', '#28a745', [
            $link('mitra-sbp', 'Performance Report', 'fa-solid fa-chart-column', 'rgb(173, 252, 157)'),
            $link('report-campaign-sbp', 'Report Campaign', 'fa-solid fa-bullhorn', '#ffc107'),
            $link('report-saldo-sbp', 'Report Saldo', 'fa-solid fa-piggy-bank', '#ffc1cc'),
        ]),
        $group('Program Campaign', 'fas fa-layer-group', null, $campaignMenus),
    ];
@endphp
<li class="nav-header">ALL DASHBOARD</li>
@foreach($productMenus as $item)
    @include('product.menu-item', ['item' => $item, 'depth' => 0])
@endforeach
<li class="nav-header">System Management</li>
@include('product.menu-item', ['item' => $link('change-password', 'Change Password', 'fas fa-key', 'rgb(173, 176, 86)'), 'depth' => 0])
<li class="nav-header">LOGOUT</li>
@include('product.menu-item', ['item' => $link('logout', 'Logout', 'fas fa-sign-out-alt', 'rgb(239,21,21)'), 'depth' => 0])
