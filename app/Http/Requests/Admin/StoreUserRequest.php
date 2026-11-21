<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('users.manage'); // checked again here, not only on the route
    }

    protected function prepareForValidation(): void
    {
        // Login lower-cases emails (Fortify), so store them the same way.
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        $role = Role::tryFrom((string) $this->input('role'));

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(Role::class)],
            // FR-14: analysts need at least one client, a client viewer exactly one,
            // and the all-client roles none.
            'client_ids' => match ($role) {
                Role::SocAnalyst => ['required', 'array', 'min:1'],
                Role::ClientViewer => ['required', 'array', 'size:1'],
                default => ['prohibited'],
            },
            'client_ids.*' => ['integer', 'distinct', Rule::exists('clients', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'client_ids.required' => 'Choose the client(s) this user works on.',
            'client_ids.min' => 'A SOC Analyst needs at least one client.',
            'client_ids.size' => 'A Client Viewer is linked to exactly one client.',
            'client_ids.prohibited' => 'Super Admin, IT Manager and Auditor see every client. Leave clients empty.',
            'client_ids.*.exists' => 'That client does not exist or is deactivated.',
        ];
    }
}