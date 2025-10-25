<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Hash;
use Log;

class RolesPermissionSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //creating roles        
        $roles = ['admin', 'customer'];
        $guards = ['web', 'api'];

        foreach ($roles as $role) {
            foreach ($guards as $guard) {
                Role::firstOrCreate([
                    'name' => $role,
                    'guard_name' => $guard
                ]);
            }
        }

        $this->createPermissions();
    }


    public function createPermissions()
    {
        // Define permissions for different areas
        $permissions = [
            // Admin Web Permissions
            'admin.dashboard.view',
            'admin.users.view',
            'admin.users.create',
            'admin.users.edit',
            'admin.users.delete',
            'admin.settings.view',
            'admin.settings.edit',
            'admin.address-types.manage',

            // Customer Web Permissions
            'customer.dashboard.view',
            'customer.profile.view',
            'customer.profile.edit',
            'customer.addresses.view',
            'customer.addresses.create',
            'customer.addresses.edit',
            'customer.addresses.delete',

            // API Permissions
            'api.admin.access',
            'api.customer.access',
        ];

        $guards = ['web', 'api'];

        // Create permissions for both guards
        foreach ($permissions as $permission) {
            foreach ($guards as $guard) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guard
                ]);
            }
        }
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }




    public function assignPermissionsToRoles()
    {
        // Admin permissions (web)
        $adminWebRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminWebRole) {
            $adminWebPermissions = [
                'admin.dashboard.view',
                'admin.users.view',
                'admin.users.create',
                'admin.users.edit',
                'admin.users.delete',
                'admin.settings.view',
                'admin.settings.edit',
                'admin.address-types.manage',
            ];

            foreach ($adminWebPermissions as $permission) {
                $permissionModel = Permission::where('name', $permission)->where('guard_name', 'web')->first();
                if ($permissionModel && !$adminWebRole->hasPermissionTo($permission, 'web')) {
                    $adminWebRole->givePermissionTo($permissionModel);
                }
            }
        }

        // Admin permissions (api)
        $adminApiRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        if ($adminApiRole) {
            $apiPermission = Permission::where('name', 'api.admin.access')->where('guard_name', 'api')->first();
            if ($apiPermission && !$adminApiRole->hasPermissionTo('api.admin.access', 'api')) {
                $adminApiRole->givePermissionTo($apiPermission);
            }
        }

        // Customer permissions (web)
        $customerWebRole = Role::where('name', 'customer')->where('guard_name', 'web')->first();
        if ($customerWebRole) {
            $customerWebPermissions = [
                'customer.dashboard.view',
                'customer.profile.view',
                'customer.profile.edit',
                'customer.addresses.view',
                'customer.addresses.create',
                'customer.addresses.edit',
                'customer.addresses.delete',
            ];

            foreach ($customerWebPermissions as $permission) {
                $permissionModel = Permission::where('name', $permission)->where('guard_name', 'web')->first();
                if ($permissionModel && !$customerWebRole->hasPermissionTo($permission, 'web')) {
                    $customerWebRole->givePermissionTo($permissionModel);
                }
            }
        }

        // Customer permissions (api)
        $customerApiRole = Role::where('name', 'customer')->where('guard_name', 'api')->first();
        if ($customerApiRole) {
            $apiPermission = Permission::where('name', 'api.customer.access')->where('guard_name', 'api')->first();
            if ($apiPermission && !$customerApiRole->hasPermissionTo('api.customer.access', 'api')) {
                $customerApiRole->givePermissionTo($apiPermission);
            }
        }

        $this->command->info('Permissions assigned to roles successfully');
    }
}
