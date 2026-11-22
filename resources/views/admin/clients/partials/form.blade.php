<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h6 fw-bold mb-3">{{ $editing ? 'Edit client: '.$editing->name : 'Add client' }}</h2>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('admin.clients.update', $editing) : route('admin.clients.store') }}" novalidate>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div class="mb-3">
                <label for="name" class="form-label small fw-semibold">Client name</label>
                <input id="name" name="name" value="{{ old('name', $editing?->name) }}" class="form-control" maxlength="150" required>
            </div>
            <div class="mb-3">
                <label for="code" class="form-label small fw-semibold">Code (letters and numbers, unique)</label>
                <input id="code" name="code" value="{{ old('code', $editing?->code) }}" class="form-control font-monospace text-uppercase"
                       maxlength="20" required aria-describedby="code-help">
                <div id="code-help" class="form-text">2 to 20 letters and numbers, for example CLA.</div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-dark">{{ $editing ? 'Save' : 'Add client' }}</button>
                <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>

        @if ($editing)
            <dl class="small border-top mt-3 pt-2 mb-0">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <dt class="fw-normal text-body-secondary">Status</dt>
                    <dd class="mb-0">
                        @if ($editing->is_active)
                            <span class="badge text-bg-success">Active</span>
                        @else
                            <span class="badge text-bg-secondary">Inactive</span>
                        @endif
                    </dd>
                </div>
                <div class="py-2">
                    <dt class="fw-normal text-body-secondary mb-1">Assigned users</dt>
                    <dd class="mb-0">
                        @forelse ($members as $member)
                            <div>{{ $member->name }} <span class="text-body-secondary">({{ $member->role?->label() }})</span></div>
                        @empty
                            <span class="text-body-secondary">None. Assign users in Users &amp; Roles.</span>
                        @endforelse
                    </dd>
                </div>
            </dl>

            <form method="POST" action="{{ $editing->is_active ? route('admin.clients.deactivate', $editing) : route('admin.clients.reactivate', $editing) }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-sm {{ $editing->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                    {{ $editing->is_active ? 'Deactivate client' : 'Reactivate client' }}
                </button>
            </form>
        @endif

        <p class="small text-body-secondary mt-3 mb-0">
            Client changes are written to the audit log. Clients are deactivated, never deleted:
            their cases and audit trail are kept for at least 2 years (NFR-7).
        </p>
    </div>
</div>