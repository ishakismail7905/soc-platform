@extends('layouts.app')

@section('section', 'My organisation')
@section('title', 'Dashboard & Incidents')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h2 class="h6 fw-bold mb-2">Welcome, {{ auth()->user()->name }}</h2>
        <p class="text-body-secondary small mb-0">
            Incidents and monthly reports for {{ auth()->user()->clientScopeLabel() }} appear here in Sprint 5.
        </p>
    </div>
</div>
@endsection