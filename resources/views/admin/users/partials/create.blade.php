<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">Create account</h2>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" novalidate>
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label small fw-semibold">Full name</label>
                <input id="name" name="name" value="{{ old('name') }}" class="form-control" maxlength="100" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Email (individual account)</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="role" class="form-label small fw-semibold">Role</label>
                <select id="role" name="role" class="form-select" required>
                    <option value="">Choose a role…</option>
                    @foreach (\App\Enums\Role::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset class="mb-3" aria-describedby="client-help">
                <legend class="form-label small fw-semibold mb-1">Assigned clients</legend>
                @forelse ($clients as $client)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="client_ids[]" value="{{ $client->id }}"
                               id="client-{{ $client->id }}"
                               @checked(in_array($client->id, old('client_ids', [])))>
                        <label class="form-check-label" for="client-{{ $client->id }}">{{ $client->name }}</label>
                    </div>
                @empty
                    <p class="small text-body-secondary mb-0">No active clients yet.</p>
                @endforelse
                <div id="client-help" class="form-text">
                    SOC Analyst: one or more. Client Viewer: exactly one. Super Admin, IT Manager and Auditor: leave empty.
                </div>
            </fieldset>
            <p class="small text-body-secondary">
                A temporary password is generated and shown once. At first sign-in the user must change it,
                and set up two-factor authentication if their role requires it.
            </p>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark">Create account</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>