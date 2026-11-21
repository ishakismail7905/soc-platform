<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Ardia Nexus SOC</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body>
<div class="d-flex min-vh-100">
    @include('layouts.partials.sidebar')

    <div class="flex-grow-1 d-flex flex-column min-w-0">
        @include('layouts.partials.topbar')

        <main class="flex-grow-1 p-3 p-lg-4">
            @foreach (['status' => 'info', 'warning' => 'warning', 'error' => 'danger'] as $key => $type)
                @if (session($key))
                    <div class="alert alert-{{ $type }} py-2 small" role="{{ $type === 'danger' ? 'alert' : 'status' }}">{{ session($key) }}</div>
                @endif
            @endforeach

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>