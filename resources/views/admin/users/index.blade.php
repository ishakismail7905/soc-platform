@extends('layouts.app')

@section('section', 'Administration')
@section('title', 'Users & Roles')

@section('content')
@include('admin.users.partials.tabs', ['active' => 'users'])

@if (session('temporary_password'))
    <div class="alert alert-warning" role="alert">
        <strong>Temporary password for {{ session('temporary_for') }}:</strong>
        <code class="fs-6 user-select-all">{{ session('temporary_password') }}</code><br>
        <span class="small">Shown only once. Give it to the user in person or by phone, never by email or chat.
            They must change it at first sign-in.</span>
    </div>
@endif

<div class="row g-3">
    <div class="{{ $panel ? 'col-xl-8' : 'col-12' }}">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0 me-auto">User accounts</h2>
                    <a href="{{ route('admin.users.create') }}" class="btn btn-dark btn-sm">+ Create account</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Role</th>
                                <th scope="col">Client scope</th>
                                <th scope="col">2FA</th>
                                <th scope="col">Last login</th>
                                <th scope="col">Status</th>
                                <th scope="col"><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $u)
                                <tr>
                                    <td>{{ $u->name }}<div class="small text-body-secondary">{{ $u->email }}</div></td>
                                    <td>{{ $u->role?->label() ?? 'No role' }}</td>
                                    <td class="small">{{ $u->clientScopeLabel() }}</td>
                                    <td>{{ $u->two_factor_confirmed_at ? 'On' : 'Off' }}</td>
                                    <td class="small">{{ $u->last_login_at?->format('d M Y, H:i') ?? 'Never' }}</td>
                                    <td>
                                        @if (! $u->is_active)
                                            <span class="badge text-bg-secondary">Deactivated</span>
                                        @elseif ($u->locked_at)
                                            <span class="badge text-bg-danger">LOCKED</span>
                                        @else
                                            <span class="badge text-bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if (Route::has('admin.users.edit'))
                                            <a href="{{ route('admin.users.edit', $u) }}" class="btn btn-outline-secondary btn-sm">
                                                {{ $u->locked_at ? 'Unlock' : 'Edit' }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($panel === 'create')
        <div class="col-xl-4">@include('admin.users.partials.create')</div>
    @elseif ($panel === 'edit')
        <div class="col-xl-4">@include('admin.users.partials.edit')</div>
    @endif
</div>
@endsection