<?php

use App\Actions\Admin\ManageCategories;
use App\Actions\Admin\ManageItems;
use App\Models\Category;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.default') !== 'mysql' || ! str_starts_with(config('database.connections.mysql.database'), 'admin_app_rbac_test_')) {
    throw new RuntimeException('Workers require a dedicated admin_app_rbac_test_* MySQL database.');
}

echo "ready\n";
flush();
try {
    if ($argv[2] === 'insert') {
        app(ManageItems::class)->save(null, ['name' => 'Concurrent item', 'category_id' => (int) $argv[1], 'link' => null], null, false);
    } else {
        app(ManageCategories::class)->delete(Category::query()->findOrFail((int) $argv[1]));
    }
    echo "changed\n";
} catch (ValidationException) {
    echo "protected\n";
}
