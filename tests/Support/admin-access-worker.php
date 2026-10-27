<?php

use App\Actions\Admin\ManageUserAccess;
use App\Actions\Admin\ManageUsers;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'mysql' || ! str_starts_with(config('database.connections.mysql.database'), 'admin_app_rbac_test_')) {
    throw new RuntimeException('Concurrency workers require a dedicated admin_app_rbac_test_* MySQL database.');
}

$user = User::query()->findOrFail((int) $argv[1]);
echo "ready\n";
flush();

try {
    $access = app(ManageUserAccess::class);
    if ($argv[2] === 'admin-delete') {
        app(ManageUsers::class)->delete($user, User::query()->findOrFail((int) $argv[3]));
    } elseif ($argv[2] === 'delete') {
        $access->deleteAccount($user);
    } else {
        $access->assignRoles($user, $user, []);
    }
    echo "removed\n";
} catch (ValidationException|AuthorizationException|ModelNotFoundException) {
    echo "protected\n";
}
