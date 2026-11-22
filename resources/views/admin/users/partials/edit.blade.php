@php($isSelf = $editing->is(auth()->user()))
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">Edit account: {{ $editing->name }}</h2>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $editing) }}" novalidate>
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label small fw-semibold">Full name</label>
                <input id="name" name="name" value="{{ old('name', $editing->name) }}" class="form-control" maxlength="100" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Email (individual account)</label>
                <input id="email" name="email" type="email" value="{{ old('email', $editing->email) }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label small fw-semibold">Role</label>
                <select id="role" name="role" class="form-select" required>
                    @foreach (\App\Enums\Role::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $editing->role?->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset class="mb-3" aria-describedby="edit-client-help">
                <legend class="form-label small fw-semibold mb-1">Assigned clients</legend>
                @forelse ($clients as $client)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="client_ids[]" value="{{ $client->id }}"
                               id="edit-client-{{ $client->id }}"
                               @checked(in_array($client->id, old('client_ids', $editing->clients->pluck('id')->all())))>
                        <label class="form-check-label" for="edit-client-{{ $client->id }}">{{ $client->name }}</label>
                    </div>
                @empty
                    <p class="small text-body-secondary mb-0">No active clients yet.</p>
                @endforelse
                <div id="edit-client-help" class="form-text">
                    SOC Analyst: one or more. Client Viewer: exactly one. Super Admin, IT Manager and Auditor: none.
                </div>
            </fieldset>
            <button type="submit" class="btn btn-dark">Save</button>
        </form>


        <dl class="small border-top mt-3 pt-2 mb-0">
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <dt class="fw-normal text-body-secondary">Two-factor</dt>
                <dd class="mb-0 d-flex gap-2 align-items-center">
                    {{ $editing->two_factor_confirmed_at ? 'Enrolled' : 'Not set up' }}
                    @if ($editing->two_factor_confirmed_at && ! $isSelf)
                        <form method="POST" action="{{ route('admin.users.reset-two-factor', $editing) }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-link btn-sm p-0">Reset 2FA</button>
                        </form>
                    @endif
                </dd>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <dt class="fw-normal text-body-secondary">Password</dt>
                <dd class="mb-0">
                    @if ($isSelf)
                        <a href="{{ route('security.edit') }}">Change in Account security</a>
                    @else
                        <form method="POST" action="{{ route('admin.users.reset-password', $editing) }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-link btn-sm p-0">Force reset at next login</button>
                        </form>
                    @endif
                </dd>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2">
                <dt class="fw-normal text-body-secondary">Status</dt>
                <dd class="mb-0 d-flex gap-2 align-items-center">
                    @if (! $editing->is_active)
                        <span class="badge text-bg-secondary">Deactivated</span>
                    @elseif ($editing->locked_at)
                        <span class="badge text-bg-danger">LOCKED</span>
                        <form method="POST" action="{{ route('admin.users.unlock', $editing) }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark btn-sm">Unlock</button>
                        </form>
                    @else
                        <span class="badge text-bg-success">Active</span>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="d-flex gap-2 mt-3">
            @unless ($isSelf)
                @if ($editing->is_active)
                    <form method="POST" action="{{ route('admin.users.deactivate', $editing) }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Deactivate now</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.users.reactivate', $editing) }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">Reactivate</button>
                    </form>
                @endif
            @endunless
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm ms-auto">Close</a>
        </div>

        <p class="small text-body-secondary mt-3 mb-0">Role, client and status changes are logged as permission changes.</p>
    </div>
</div>