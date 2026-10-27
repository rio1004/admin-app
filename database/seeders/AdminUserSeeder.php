<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the explicitly requested dashboard preview account.
     */
    public function run(): void
    {
        abort_unless(app()->environment('local'), 403, 'The preview account seeder is local-only. Use admin:make for administrator setup.');

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => 'admin@Admin123',
                'email_verified_at' => now(),
            ],
        );
    }
}
