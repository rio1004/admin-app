<?php

namespace App\Actions\Admin;

use Closure;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

final class AccessLock
{
    /** @template T
     * @param  Closure(): T  $callback
     * @return T
     */
    public function run(Closure $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            if (! DB::table('admin_access_locks')->where('id', 1)->lockForUpdate()->first()) {
                throw new RuntimeException('The admin access lock is missing. Run migrations.');
            }

            // Route checks may have populated an in-memory permission map before
            // another transaction committed. Recheck writes against current grants.
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $callback();
        }, 3);
    }
}
