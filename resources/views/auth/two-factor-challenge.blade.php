@extends('layouts.guest')

@section('title', 'Two-factor verification')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h1 class="h4 mb-1">Two-factor verification</h1>
        <p class="text-body-secondary small mb-4">Enter the 6-digit code from your authenticator app.</p>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/two-factor-challenge') }}">
            @csrf
            <div class="mb-4">
                <label for="code" class="form-label">Authentication code</label>
                <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                       autocomplete="one-time-code" class="form-control form-control-lg font-monospace text-center" autofocus required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Verify</button>
        </form>

        <details class="mt-4">
            <summary class="small">Lost your device? Use a recovery code</summary>
            <form method="POST" action="{{ url('/two-factor-challenge') }}" class="mt-3">
                @csrf
                <label for="recovery_code" class="form-label">Recovery code</label>
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="off" class="form-control font-monospace mb-3" required>
                <button type="submit" class="btn btn-outline-secondary w-100">Use recovery code</button>
            </form>
        </details>
    </div>
</div>
@endsection