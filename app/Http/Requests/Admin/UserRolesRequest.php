<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('admin.access') ?? false) && $this->user()->can('roles.update');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['roles' => ['present', 'array', 'max:100'],
            'roles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')]];
    }
}
