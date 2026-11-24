<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateUserRequest extends StoreUserRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['email'] = ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))];

        return $rules;
    }
}
