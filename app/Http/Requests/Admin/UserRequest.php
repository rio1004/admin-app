<?php

namespace App\Http\Requests\Admin;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User ? ($this->user()?->can('update', $target) ?? false)
            : ($this->user()?->can('create', User::class) ?? false);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $target = $this->route('user');
        $password = $this->passwordRules();
        if ($target instanceof User) {
            $password[0] = 'nullable';
        }

        return [
            ...$this->profileRules($target instanceof User ? $target->id : null),
            'password' => $password,
            'roles' => $this->user()?->can('roles.update') ? ['sometimes', 'array', 'max:100'] : ['missing'],
            'roles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'role' => ['missing'], 'role_id' => ['missing'], 'permissions' => ['missing'],
            'email_verified_at' => ['missing'], 'remember_token' => ['missing'],
            'two_factor_secret' => ['missing'], 'two_factor_recovery_codes' => ['missing'],
        ];
    }
}
