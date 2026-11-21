<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link{{ $active === 'users' ? ' active' : '' }}" @if ($active === 'users') aria-current="page" @endif
           href="{{ route('admin.users.index') }}">Users</a>
    </li>
    <li class="nav-item">
        <a class="nav-link{{ $active === 'permissions' ? ' active' : '' }}" @if ($active === 'permissions') aria-current="page" @endif
           href="{{ route('admin.users.permissions') }}">Role permissions (Table 3.2)</a>
    </li>
</ul>