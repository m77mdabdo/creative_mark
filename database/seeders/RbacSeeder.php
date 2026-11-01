<?php

namespace Database\Seeders;

use App\Auth\PermissionMatrix;
use App\Enums\Role as RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Syncs roles and permissions with App\Auth\PermissionMatrix. Safe to run repeatedly. */
class RbacSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(PermissionMatrix::all()) as $name) {
            Permission::findOrCreate($name, 'web');
        }
        Permission::query()->whereNotIn('name', array_keys(PermissionMatrix::all()))->delete();

        foreach (RoleName::cases() as $role) {
            Role::findOrCreate($role->value, 'web')->syncPermissions(PermissionMatrix::permissionsFor($role));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
