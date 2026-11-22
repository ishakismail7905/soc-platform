@extends('layouts.app')

@section('section', 'Audit')
@section('title', 'Activity Log')

@section('content')
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" aria-current="page" href="{{ route('audit.index') }}">User activity log</a>
    </li>
    <li class="nav-item">
        <span class="nav-link disabled" aria-disabled="true" title="Arrives with incident cases in Sprint 4">Case audit trail</span>
    </li>
</ul>

@if ($errors->any())
    <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
@endif

<form method="GET" action="{{ route('audit.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-3">
            <label for="event" class="form-label small fw-semibold">Event</label>
            <select id="event" name="event" class="form-select form-select-sm">
                <option value="">All events</option>
                @foreach ($events as $event)
                    <option value="{{ $event }}" @selected(($filters['event'] ?? null) === $event)>{{ $event }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="user_id" class="form-label small fw-semibold">User</label>
            <select id="user_id" name="user_id" class="form-select form-select-sm">
                <option value="">All users</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="client_id" class="form-label small fw-semibold">Client</label>
            <select id="client_id" name="client_id" class="form-select form-select-sm">
                <option value="">All clients</option>
                @foreach ($clients as $c)
                    <option value="{{ $c->id }}" @selected((string) ($filters['client_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="from" class="form-label small fw-semibold">From</label>
            <input id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label for="to" class="form-label small fw-semibold">To</label>
            <input id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm">Filter</button>
            <a href="{{ route('audit.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            @can('audit.export')
                <a href="{{ route('audit.export', request()->query()) }}" class="btn btn-outline-dark btn-sm ms-auto">Export extract (CSV)</a>
            @endcan
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Time (MYT)</th>
                        <th scope="col">User</th>
                        <th scope="col">Role</th>
                        <th scope="col">Event</th>
                        <th scope="col">Object</th>
                        <th scope="col">Detail</th>
                        <th scope="col">IP address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $actor = $log->user_id ? $people->get($log->user_id) : null;
                            $target = ($log->subject_type === \App\Models\User::class && $log->subject_id) ? $people->get($log->subject_id) : null;
                            $roleLabel = \App\Enums\Role::tryFrom((string) ($log->role ?? ($log->properties['target_role'] ?? '')))?->label();
                            $detail = \App\Support\AuditDetail::summarise($log);
                        @endphp
                        <tr>
                            <td class="font-monospace text-nowrap" title="{{ $log->occurred_at?->format('d M Y H:i:s') }}">{{ $log->occurred_at?->format('d M H:i:s') }}</td>
                            <td>
                                @if ($actor)
                                    {{ $actor->name }}
                                @elseif ($target)
                                    {{ $target->name }} <span class="text-body-secondary">(target)</span>
                                @else
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td>{{ $roleLabel ?? '—' }}</td>
                            <td><span class="badge text-bg-light border font-monospace">{{ $log->event }}</span></td>
                            <td class="text-nowrap">{{ $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : '—' }}</td>
                            <td title="{{ $detail }}">{{ \Illuminate\Support\Str::limit($detail, 90) }}</td>
                            <td class="font-monospace">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-body-secondary">No entries match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $logs->links() }}</div>

        <p class="small text-body-secondary mt-2 mb-0">Read-only for every role. Entries are append-only and cannot be edited or deleted.</p>
    </div>
</div>
@endsection