@extends('layouts.guest')

@section('title', 'Account security')

@section('content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-4">
        <h1 class="h4 mb-1">Account security</h1>
        <p class="text-body-secondary small mb-0">Signed in as {{ $user->email }}</p>
    </div>
</div>

@if (session('warning'))
    <div class="alert alert-warning py-2 small" role="alert">{{ session('warning') }}</div>
@endif
@if (session('status') === 'password-updated')
    <div class="alert alert-success py-2 small" role="status">Password changed.</div>
@endif

{{-- 1. Password --}}
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-4">
        <h2 class="h5 mb-1">Change password</h2>
        <p class="text-body-secondary small mb-3">
            @if ($user->must_change_password)
                You are using a temporary password. Choose your own before you continue.
            @else
                At least 12 characters. A long phrase is easier to remember than a short complex word.
            @endif
        </p>

        @if ($errors->updatePassword->any())
            <div class="alert alert-danger py-2 small" role="alert">{{ $errors->updatePassword->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/user/password') }}">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="current_password" class="form-label">Current password</label>
                <input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New password</label>
                <input id="new_password" name="password" type="password" class="form-control" autocomplete="new-password" minlength="12" maxlength="64" required>
            </div>
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Repeat new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Change password</button>
        </form>
    </div>
</div>

{{-- 2. Two-factor --}}
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body p-4">
        @if ($twoFactorOn)
            <h2 class="h5 mb-1">Two-factor authentication</h2>
            <p class="small mb-3"><span class="badge text-bg-success">On</span> Sign-in asks for a code from your authenticator app.</p>

            @if ($showRecoveryCodes)
                <div class="alert alert-warning py-2 small" role="alert">
                    <strong>Save these recovery codes now.</strong> Each one works once if you lose your phone. They will not be shown again.
                </div>
                <ul class="list-unstyled font-monospace small border rounded p-3 mb-3">
                    @foreach ($user->recoveryCodes() as $code)
                        <li>{{ $code }}</li>
                    @endforeach
                </ul>
            @endif

            <a href="{{ route('dashboard') }}" class="btn btn-primary w-100 mb-2">Continue to dashboard</a>

            @unless ($roleRequiresTwoFactor)
                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100">Turn off two-factor</button>
                </form>
            @endunless

        @elseif ($twoFactorPending)
            <h2 class="h5 mb-1">Set up two-factor authentication</h2>
            <p class="small mb-2">1. Scan this QR code with Google Authenticator, Microsoft Authenticator or Aegis.</p>
            <div class="text-center mb-3">{!! $user->twoFactorQrCodeSvg() !!}</div>
            <p class="small mb-1">Can't scan it? Type this setup key into the app instead:</p>
            <p class="font-monospace small border rounded p-2 text-center">{{ trim(chunk_split(decrypt($user->two_factor_secret), 4, ' ')) }}</p>
            <p class="small mb-2">2. Enter the 6-digit code the app now shows.</p>

            @if ($errors->confirmTwoFactorAuthentication->any())
                <div class="alert alert-danger py-2 small" role="alert">{{ $errors->confirmTwoFactorAuthentication->first() }}</div>
            @endif

            <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}">
                @csrf
                <input name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                       class="form-control form-control-lg font-monospace text-center mb-3" aria-label="6-digit code" required>
                <button type="submit" class="btn btn-primary w-100">Confirm and save recovery codes</button>
            </form>

        @else
            <h2 class="h5 mb-1">Set up two-factor authentication</h2>
            <p class="text-body-secondary small mb-3">
                {{ $roleRequiresTwoFactor ? 'Required for your role.' : 'Optional for your role, but recommended.' }}
                You will need an authenticator app on your phone.
            </p>

            @if ($passwordConfirmed)
                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100">Start setup</button>
                </form>
            @else
                @error('password')
                    <div class="alert alert-danger py-2 small" role="alert">{{ $message }}</div>
                @enderror
                <form method="POST" action="{{ url('/user/confirm-password') }}">
                    @csrf
                    <label for="confirm_password" class="form-label small">Confirm your password to start</label>
                    <input id="confirm_password" name="password" type="password" class="form-control mb-3" autocomplete="current-password" required>
                    <button type="submit" class="btn btn-primary w-100">Confirm password</button>
                </form>
            @endif
        @endif
    </div>
</div>

<form method="POST" action="{{ route('logout') }}" class="text-center">
    @csrf
    <button type="submit" class="btn btn-link btn-sm text-body-secondary">Sign out</button>
</form>
@endsection