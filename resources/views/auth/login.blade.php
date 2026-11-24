@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h1 class="h4 mb-1">Sign in</h1>
        <p class="text-body-secondary small mb-4">Use your individual account. Shared logins are not allowed.</p>

        @if (session('status'))
            <div class="alert alert-info py-2 small" role="status">{{ session('status') }}</div>
        @endif

        @if (session('timeout'))
            <div class="alert alert-warning py-2 small" role="status">
                <strong class="d-block">Session ended</strong>
                You were signed out after 15 minutes of inactivity or because the browser was closed. Sign in again to continue.
            </div>
        @endif
        
        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Email (individual account)</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       class="form-control" required autofocus autocomplete="username">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input id="password" type="password" name="password"
                       class="form-control" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>

        <p class="text-body-secondary small text-center mt-4 mb-0">
            Forgot your password? Contact your Super Admin.<br>
            Accounts lock after 5 failed attempts.<br>
            HTTPS only · No shared logins
        </p>
    </div>
</div>
@endsection