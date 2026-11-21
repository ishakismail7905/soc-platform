@extends('layouts.app')

@section('section', 'Read-only')
@section('title', 'Audit access')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h2 class="h6 fw-bold mb-2">Welcome, {{ auth()->user()->name }}</h2>
        <p class="text-body-secondary small mb-0">
            The activity log arrives in Task 10 and the case audit trail in Sprint 4. Everything here is read-only.
        </p>
    </div>
</div>
@endsection