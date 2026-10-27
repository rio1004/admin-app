<?php

use App\Actions\Admin\ManageCategories;
use App\Actions\Admin\ManageItems;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

beforeEach(function () {
    if (config('database.default') !== 'mysql' || ! str_starts_with(config('database.connections.mysql.database'), 'admin_app_rbac_test_')) {
        $this->markTestSkipped('Requires a dedicated admin_app_rbac_test_* MySQL database.');
    }
    Artisan::call('migrate', ['--force' => true]);
});

test('category insert delete races preserve referential integrity', function (string $operation) {
    $category = Category::factory()->create();
    $process = null;
    try {
        DB::transaction(function () use ($category, $operation, &$process) {
            Category::query()->whereKey($category->id)->lockForUpdate()->firstOrFail();
            if ($operation === 'delete') {
                app(ManageItems::class)->save(null, ['name' => 'Committed item', 'category_id' => $category->id, 'link' => null], null, false);
            }
            $process = new Process([PHP_BINARY, base_path('tests/Support/category-item-worker.php'), (string) $category->id, $operation], base_path(), [
                'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => config('database.connections.mysql.database'),
                'DB_URL' => '', 'CACHE_STORE' => 'array',
            ]);
            $process->setTimeout(20);
            $process->start();
            expect($process->waitUntil(fn ($type, $output) => str_contains($output, 'ready')))->toBeTrue();
            expect($process->isRunning())->toBeTrue();
            expect($process->getOutput())->not->toContain('changed');
            if ($operation === 'insert') {
                app(ManageCategories::class)->delete($category);
            }
        });
        $process->wait();
        expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
        expect($process->getOutput())->toContain('protected')->not->toContain('changed');
        if ($operation === 'insert') {
            expect($category->fresh())->toBeNull()->and(Item::where('category_id', $category->id)->count())->toBe(0);
        } else {
            expect($category->fresh())->not->toBeNull()->and(Item::where('category_id', $category->id)->count())->toBe(1);
        }
    } finally {
        if ($process?->isRunning()) {
            $process->stop();
        }
        DB::table('items')->where('category_id', $category->id)->delete();
        DB::table('categories')->where('id', $category->id)->delete();
    }
})->with(['insert', 'delete']);
