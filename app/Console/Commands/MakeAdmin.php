<?php

namespace App\Console\Commands;

use App\Actions\Admin\AccessLock;
use App\Models\User;
use App\Support\AdminPermissions;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeAdmin extends Command
{
    protected $signature = 'admin:make {email : Existing account email} {--create : Create the account if it does not exist}';

    protected $description = 'Explicitly grant the protected Admin role; never reset an existing password';

    public function handle(AccessLock $lock): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->error('Provide a valid email address.');

            return self::FAILURE;
        }
        $user = User::query()->where('email', $email)->first();
        $name = null;
        $username = null;
        $password = null;
        if (! $user) {
            if (! $this->option('create') || ! $this->input->isInteractive()) {
                $this->error('Account not found. Use --create interactively to enter a name and password.');

                return self::FAILURE;
            }
            $name = $this->ask('Name');
            $username = strtolower(trim((string) $this->ask('Username (letters, numbers, underscores and hyphens)')));
            $password = $this->secret('Password (minimum 12 characters, mixed case, number and symbol)');
            $confirmation = $this->secret('Confirm password');
            $validator = Validator::make(['name' => $name, 'username' => $username, 'password' => $password, 'password_confirmation' => $confirmation], [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/', 'unique:users,username'],
                'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            ]);
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }
        }
        (new RolesAndPermissionsSeeder)->run();
        $lock->run(function () use ($user, $email, $name, $username, $password) {
            if (! $user) {
                $user = User::query()->create(['name' => $name, 'username' => $username, 'email' => $email, 'password' => $password]);
            }
            $user->assignRole(AdminPermissions::ADMIN_ROLE);
        });
        $user = User::query()->where('email', $email)->firstOrFail();
        $this->info("Admin access granted to $email. Username: {$user->username}. Existing credentials are unchanged.");

        return self::SUCCESS;
    }
}
