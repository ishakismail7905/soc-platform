@extends('layouts.guest')

@section('title', 'Confirm password')

@section('content')
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h1 class="h4 mb-1">Confirm your password</h1>
        <p class="text-body-secondary small mb-4">For your security, enter your password again before changing two-factor settings.</p>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/user/confirm-password') }}">
            @csrf
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" autofocus required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Confirm</button>
        </form>
    </div>
</div>
@endsection