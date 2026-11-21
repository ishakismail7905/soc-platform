@extends('layouts.guest')

@section('title', 'Access denied')

@section('content')
<div class="card shadow-sm border-0 text-center">
    <div class="card-body p-4">
        <div class="display-6 fw-bold text-body-secondary mb-2">403</div>
        <h1 class="h5 mb-2">You don't have access to this page</h1>
        <p class="text-body-secondary small mb-4">
            Your role does not allow this action, or the record is outside your client scope.
        </p>
        <a href="{{ url('/') }}" class="btn btn-primary w-100">Back to my dashboard</a>
    </div>
</div>
@endsection