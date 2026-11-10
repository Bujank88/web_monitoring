@php
    $hasChildren = !empty($item['children']);
    $isActive = request()->routeIs(...$item['matches']);
@endphp
<li class="nav-item {{ $hasChildren ? 'has-treeview' : '' }} {{ $hasChildren && $isActive ? 'menu-open' : '' }}">
    <a href="{{ $hasChildren ? '#' : route($item['route']) }}"
        class="nav-link waves-effect {{ $isActive ? 'active' : '' }}"
        @if($depth > 0) style="padding-left: {{ 25 + $depth * 20 }}px;" @endif>
        <i class="nav-icon {{ $item['icon'] }}" @if($item['color']) style="color:{{ $item['color'] }};" @endif></i>
        <p>
            {{ $item['label'] }}
            @if($hasChildren)
                <i class="right fas fa-angle-left"></i>
            @endif
        </p>
    </a>
    @if($hasChildren)
        <ul class="nav nav-treeview">
            @foreach($item['children'] as $child)
                @include('product.menu-item', ['item' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
