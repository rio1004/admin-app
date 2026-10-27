<?php

use App\Actions\Admin\AccessLock;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

beforeEach(function () {
    if (config('database.default') !== 'mysql' || ! str_starts_with(config('database.connections.mysql.database'), 'admin_app_rbac_test_')) {
        $this->markTestSkipped('Requires a dedicated admin_app_rbac_test_* MySQL database.');
    }
    Artisan::call('migrate', ['--force' => true]);
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('concurrent administrator removals preserve the final administrator', function (string $operation) {
    expect(User::role('Admin')->count())->toBe(0);
    $users = User::factory()->count(2)->create();
    foreach ($users as $user) {
        $user->assignRole('Admin');
    }
    $processes = [];
    try {
        app(AccessLock::class)->run(function () use ($users, $operation, &$processes) {
            foreach ($users as $user) {
                $other = $users->firstWhere('id', '!=', $user->id);
                $process = new Process([PHP_BINARY, base_path('tests/Support/admin-access-worker.php'), (string) $user->id, $operation, (string) $other->id], base_path(), [
                    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql',
                    'DB_DATABASE' => config('database.connections.mysql.database'),
                    'DB_URL' => '', 'CACHE_STORE' => 'array',
                ]);
                $process->setTimeout(20);
                $process->start();
                $processes[] = $process;
                expect($process->waitUntil(fn ($type, $output) => str_contains($output, 'ready')))->toBeTrue();
            }
            // Both workers have started while the parent holds the shared lock.
            foreach ($processes as $process) {
                expect($process->isRunning())->toBeTrue();
                expect($process->getOutput())->not->toContain('removed');
            }
        });
        $outputs = [];
        foreach ($processes as $process) {
            $process->wait();
            expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
            $outputs[] = $process->getOutput();
        }
        expect(collect($outputs)->filter(fn ($output) => str_contains($output, 'removed')))->toHaveCount(1);
        expect(collect($outputs)->filter(fn ($output) => str_contains($output, 'protected')))->toHaveCount(1);
        expect(User::role('Admin')->count())->toBe(1);
    } finally {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        // Fixture cleanup is confined to the explicitly isolated test database.
        DB::table('model_has_roles')->where('model_type', (new User)->getMorphClass())->whereIn('model_id', $users->modelKeys())->delete();
        DB::table('users')->whereIn('id', $users->modelKeys())->delete();
    }
})->with(['demote', 'delete', 'admin-delete']);
