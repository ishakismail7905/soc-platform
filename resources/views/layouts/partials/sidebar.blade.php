<aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="appSidebar" aria-label="Main menu">
    <div class="offcanvas-body p-0">
        <div class="sidebar-inner d-flex flex-column p-3">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none px-1 mb-1">
                <span class="brand-mark" aria-hidden="true">AN</span>
                <span>
                    <span class="d-block fw-bold small">Ardia Nexus SOC</span>
                    <span class="d-block sidebar-subtitle">Management System</span>
                </span>
            </a>

            <nav aria-label="Main">
                @foreach ($navigation as $section)
                    <div class="sidebar-heading">{{ $section['heading'] }}</div>
                    <ul class="nav flex-column">
                        @foreach ($section['items'] as $item)
                            <li class="nav-item">
                                @if ($item['url'])
                                    <a class="nav-link{{ $item['active'] ? ' active' : '' }}" @if ($item['active']) aria-current="page" @endif href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                                @else
                                    <span class="nav-link disabled" aria-disabled="true" title="Coming in a later sprint">{{ $item['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </nav>

            <div class="sidebar-footer mt-auto pt-3 px-1">
                Auto logout after 15 min idle<br>
                Signed in as: {{ auth()->user()->role?->label() ?? 'No role' }}
            </div>
        </div>
    </div>
</aside>