<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManageUsers
{
    public function __construct(private AccessLock $lock, private ManageUserAccess $access) {}

    /**
     * @param  array{name: string, email: string, password: string|null}  $data
     * @param  list<int>|null  $roles  Null means preserve assignments; [] means clear.
     */
    public function save(User $actor, ?User $target, array $data, ?array $roles): User
    {
        try {
            return $this->lock->run(function () use ($actor, $target, $data, $roles) {
                $actor = User::query()->findOrFail($actor->id);
                $target = $target ? User::query()->findOrFail($target->id) : null;
                Gate::forUser($actor)->authorize($target ? 'update' : 'create', $target ?? User::class);
                if ($roles !== null) {
                    Gate::forUser($actor)->authorize('roles.update');
                }
                $target ??= new User;
                $target->name = $data['name'];
                if ($target->email !== $data['email']) {
                    if ($target->exists) {
                        DB::table('password_reset_tokens')->where('email', $target->email)->delete();
                    }
                    $target->email_verified_at = null;
                }
                $target->email = $data['email'];
                if ($data['password'] !== null && $data['password'] !== '') {
                    $target->password = $data['password'];
                    if ($target->exists) {
                        $target->remember_token = Str::random(60);
                        DB::table('password_reset_tokens')->where('email', $target->email)->delete();
                    }
                }
                $target->save();
                if ($roles !== null) {
                    $this->access->assignRoles($actor, $target, $roles);
                }

                return $target;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['email' => 'The email has already been taken.']);
        }
    }

    public function delete(User $actor, User $target): void
    {
        $this->lock->run(function () use ($actor, $target) {
            $actor = User::query()->findOrFail($actor->id);
            $target = User::query()->findOrFail($target->id);
            Gate::forUser($actor)->authorize('delete', $target);
            $this->access->deleteAccount($target);
        });
    }
}
