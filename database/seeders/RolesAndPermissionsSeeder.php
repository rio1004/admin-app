<?php

namespace Database\Seeders;

use App\Actions\Admin\AccessLock;
use App\Models\Role;
use App\Support\AdminPermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(AccessLock::class)->run(function () {
            foreach (AdminPermissions::all() as $name) {
                Permission::findOrCreate($name, 'web');
            }

            Role::findOrCreate(AdminPermissions::ADMIN_ROLE, 'web')
                ->syncPermissions(AdminPermissions::all());
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
