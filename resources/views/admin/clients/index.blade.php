@extends('layouts.app')

@section('section', 'Administration')
@section('title', 'Clients')

@section('content')
<div class="row g-3">
    <div class="{{ $panel ? 'col-xl-8' : 'col-12' }}">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0 me-auto">Client records</h2>
                    <a href="{{ route('admin.clients.create') }}" class="btn btn-dark btn-sm">+ Add client</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Client</th>
                                <th scope="col">Code</th>
                                <th scope="col">Assigned users</th>
                                <th scope="col">Cases</th>
                                <th scope="col">Created</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($clients as $c)
                                <tr>
                                    <td>{{ $c->name }}</td>
                                    <td class="font-monospace">{{ $c->code }}</td>
                                    <td class="small">{{ $c->analysts_count }} {{ \Illuminate\Support\Str::plural('analyst', $c->analysts_count) }} · {{ $c->viewers_count }} {{ \Illuminate\Support\Str::plural('viewer', $c->viewers_count) }}</td>
                                    <td class="text-body-secondary" title="Available when cases arrive in Sprint 4">—</td>
                                    <td class="small">{{ $c->created_at?->format('d M Y') }}</td>
                                    <td>
                                        @if ($c->is_active)
                                            <span class="badge text-bg-success">Active</span>
                                        @else
                                            <span class="badge text-bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.clients.edit', $c) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="small text-body-secondary">No clients yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($panel === 'create')
        <div class="col-xl-4">@include('admin.clients.partials.form', ['editing' => null])</div>
    @elseif ($panel === 'edit')
        <div class="col-xl-4">@include('admin.clients.partials.form')</div>
    @endif
</div>

<div class="card border-0 shadow-sm mt-3">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-2">Client separation rules (FR-14)</h2>
        <ul class="small mb-0 ps-3">
            <li>Users are linked to clients in Users &amp; Roles. SOC Analysts see only the clients assigned to them; a Client Viewer is linked to exactly one client.</li>
            <li>Super Admin, IT Manager and Auditor see every client. A user with no role sees no client (fail closed).</li>
            <li>Opening another client's record returns the same 403 page as a record that does not exist, and the attempt is logged as ACCESS_DENIED.</li>
            <li>A deactivated client keeps its records but cannot receive new alerts or new user assignments.</li>
        </ul>
    </div>
</div>
@endsection