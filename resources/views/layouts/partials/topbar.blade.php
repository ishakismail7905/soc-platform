@php($user = auth()->user())
<header class="app-topbar bg-white border-bottom d-flex align-items-center gap-3 px-3 px-lg-4 py-2">
    <button class="btn btn-sm btn-outline-secondary d-lg-none" type="button"
            data-bs-toggle="offcanvas" data-bs-target="#appSidebar" aria-controls="appSidebar">Menu</button>

    <div class="me-auto">
        <div class="text-body-secondary small">@yield('section')</div>
        <h1 class="h5 fw-bold mb-0">@yield('title')</h1>
    </div>

    <span class="badge rounded-pill border bg-light text-body fw-normal d-none d-md-inline-block">{{ $user->role?->label() ?? 'No role' }} · {{ $user->clientScopeLabel() }}</span>
    <span class="small text-body-secondary d-none d-md-inline">{{ now()->format('d M Y, H:i') }} MYT</span>
    <span class="avatar" aria-hidden="true">{{ $user->initials() }}</span>

    <form method="POST" action="{{ route('logout') }}" class="m-0">
        @csrf
        <button type="submit" class="btn btn-link btn-sm text-body-secondary text-decoration-none">Log out</button>
    </form>
</header>