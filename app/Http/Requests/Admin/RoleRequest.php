<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use App\Support\AdminPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');

        return $role instanceof Role
            ? ($this->user()?->can('update', $role) ?? false)
            : ($this->user()?->can('create', Role::class) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/',
                Rule::notIn([AdminPermissions::ADMIN_ROLE]),
                Rule::unique('roles')->where('guard_name', 'web')->ignore($role instanceof Role ? $role->id : null)],
            'permissions' => ['present', 'array', 'max:'.count(AdminPermissions::all())],
            'permissions.*' => ['required', 'string', 'distinct', Rule::in(AdminPermissions::all())],
            'guard_name' => ['prohibited'],
            'users' => ['prohibited'],
            'roles' => ['prohibited'],
        ];
    }
}
