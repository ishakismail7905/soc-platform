<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('clients.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => Str::upper(trim((string) $this->input('code'))),
        ]);
    }

    public function rules(): array
    {
        $client = $this->route('client'); // null when adding, the client when editing

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('clients', 'name')->ignore($client)],
            'code' => ['required', 'regex:/^[A-Z0-9]{2,20}$/', Rule::unique('clients', 'code')->ignore($client)],
        ];
    }

    public function messages(): array
    {
        return ['code.regex' => 'Use 2 to 20 letters and numbers only, for example CLA.'];
    }
}