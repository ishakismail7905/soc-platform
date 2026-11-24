<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') · Ardia Nexus SOC</title>
    @vite(['resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center min-vh-100 py-4">
    <main class="container auth-container">
        <div class="text-center mb-4">
            <div class="fw-bold fs-4">Ardia Nexus SOC</div>
            <div class="text-body-secondary small">Centralized SOC Management System</div>
        </div>
        @yield('content')
    </main>
</body>
</html>