@extends('layouts.app')

@section('section', 'Client')
@section('title', $client->name)

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <dl class="row small mb-3">
            <dt class="col-sm-3 fw-normal text-body-secondary">Code</dt>
            <dd class="col-sm-9 font-monospace">{{ $client->code }}</dd>
            <dt class="col-sm-3 fw-normal text-body-secondary">Status</dt>
            <dd class="col-sm-9">{{ $client->is_active ? 'Active' : 'Inactive' }}</dd>
            <dt class="col-sm-3 fw-normal text-body-secondary">Client since</dt>
            <dd class="col-sm-9">{{ $client->created_at?->format('d M Y') }}</dd>
        </dl>
        <p class="small text-body-secondary mb-0">Alerts and incident cases for this client appear here from Sprints 2 and 4.</p>
    </div>
</div>
@endsection