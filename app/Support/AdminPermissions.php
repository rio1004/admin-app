<?php

namespace App\Support;

final class AdminPermissions
{
    public const ADMIN_ROLE = 'Admin';

    /** @return list<string> */
    public static function all(): array
    {
        $permissions = ['admin.access', 'dashboard.view'];

        foreach (['roles', 'users', 'items', 'categories'] as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $action) {
                $permissions[] = "$resource.$action";
            }
        }

        return $permissions;
    }
}
