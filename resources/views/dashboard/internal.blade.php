@extends('layouts.app')

@section('section', 'Overview')
@section('title', $title)

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h2 class="h6 fw-bold mb-2">Welcome, {{ auth()->user()->name }}</h2>
        <p class="text-body-secondary small mb-0">
            Open cases, response targets, alert volume and analyst workload appear here in Sprint 5,
            once alerts and cases exist.
        </p>
    </div>
</div>
@endsection