<li class="nav-header">Leads</li>
<li class="nav-item {{ request()->routeIs('spectrum.*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->routeIs('spectrum.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-layer-group" style="color:#17a2b8;"></i>
        <p>Spectrum <i class="right fas fa-angle-left"></i></p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('spectrum.leads.create') }}" class="nav-link {{ request()->routeIs('spectrum.leads.create') ? 'active' : '' }}" style="padding-left:45px;">
                <i class="nav-icon fas fa-user-plus" style="color:#17a2b8;"></i><p>Input Leads</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('spectrum.leads.index') }}" class="nav-link {{ request()->routeIs('spectrum.leads.index') ? 'active' : '' }}" style="padding-left:45px;">
                <i class="nav-icon fas fa-list" style="color:#17a2b8;"></i><p>Data Leads</p>
            </a>
        </li>
    </ul>
</li>
<li class="nav-header">System Management</li>
<li class="nav-item">
    <a href="{{ route('change-password') }}" class="nav-link {{ request()->routeIs('change-password') ? 'active' : '' }}">
        <i class="nav-icon fas fa-key" style="color:#adb056;"></i><p>Change Password</p>
    </a>
</li>
<li class="nav-header">LOGOUT</li>
<li class="nav-item">
    <a href="{{ route('logout') }}" class="nav-link">
        <i class="nav-icon fas fa-sign-out-alt" style="color:#ef1515;"></i><p>Logout</p>
    </a>
</li>
