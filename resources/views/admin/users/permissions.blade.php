@extends('layouts.app')

@section('section', 'Administration')
@section('title', 'Users & Roles')

@section('content')
@include('admin.users.partials.tabs', ['active' => 'permissions'])

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-1">Role permissions (Table 3.2)</h2>
        <p class="small text-body-secondary">
            Generated from the permission matrix the server enforces, so this table always matches what each role can do.
        </p>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Permission</th>
                        @foreach ($roles as $role)
                            <th scope="col" class="text-center">{{ $role->label() }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $permission)
                        <tr>
                            <td class="font-monospace small">{{ $permission->value }}</td>
                            @foreach ($roles as $role)
                                <td class="text-center">
                                    @if ($role->allows($permission))
                                        <span class="badge text-bg-success">Yes</span>
                                    @else
                                        <span aria-hidden="true" class="text-body-secondary">—</span><span class="visually-hidden">No</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection