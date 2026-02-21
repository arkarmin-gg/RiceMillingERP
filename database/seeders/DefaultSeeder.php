<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultSeeder extends Seeder
{
    public function run(): void
    {
        $modulesToSeed = [
            [
                'name' => 'Users',
                'code' => 'USERS',
            ],
            [
                'name' => 'Admins',
                'code' => 'ADMINS',
            ],
            [
                'name' => 'Roles',
                'code' => 'ROLES',
            ],
            [
                'name' => 'Activity Logs',
                'code' => 'ACTIVITY_LOGS',
            ],
            [
                'name' => 'Settings',
                'code' => 'SETTINGS',
                'children' => [
                    [
                        'name' => 'SMTP Settings',
                        'code' => 'SMTP_SETTINGS',
                    ],
                ],
            ],
        ];

        $createdModules = collect();

        foreach ($modulesToSeed as $moduleSeed) {
            $module = Module::firstOrCreate(
                ['code' => $moduleSeed['code']],
                [
                    'name' => $moduleSeed['name'],
                ],
            );

            $createdModules->push($module);

            if (! empty($moduleSeed['children'])) {
                foreach ($moduleSeed['children'] as $childSeed) {
                    $child = Module::firstOrCreate(
                        [
                            'code' => $childSeed['code'],
                            'parent_id' => $module->id,
                        ],
                        [
                            'name' => $childSeed['name'],
                        ],
                    );

                    $createdModules->push($child);
                }
            }
        }

        $actions = ['CREATE', 'READ', 'UPDATE', 'DELETE'];

        $modulePermissions = [];

        foreach ($createdModules as $module) {
            $permissions = [];

            foreach ($actions as $action) {
                $permission = Permission::firstOrCreate(
                    [
                        'module_id' => $module->id,
                        'action' => $action,
                    ],
                );

                $permissions[] = $permission;
            }

            $modulePermissions[$module->code] = $permissions;
        }

        $roleConfigs = $this->getRoleConfigurations(array_keys($modulePermissions));

        $createdRoles = collect();

        foreach ($roleConfigs as $config) {
            $role = Role::firstOrCreate(
                ['name' => $config['name']],
                ['description' => $config['description']],
            );

            $createdRoles->push($role);

            $this->assignPermissionsToRoleFromConfig(
                $role,
                $config['modules'],
                $modulePermissions,
            );
        }

        $superAdminRole = $createdRoles->firstWhere('name', 'Super Admin');

        if ($superAdminRole) {
            $this->createSuperAdmin($superAdminRole);
        }

        $this->createNormalUser();
    }

    private function getRoleConfigurations(array $allModules): array
    {
        $allPermissions = ['CREATE', 'READ', 'UPDATE', 'DELETE'];

        $moduleAccess = [];

        foreach ($allModules as $module) {
            $moduleAccess[$module] = $allPermissions;
        }

        return [
            [
                'name' => 'Super Admin',
                'description' => 'Super Administrator role with full access',
                'modules' => $moduleAccess,
            ],
        ];
    }

    private function assignPermissionsToRoleFromConfig(
        Role $role,
        array $moduleConfig,
        array $modulePermissions,
    ): void {
        foreach ($moduleConfig as $moduleCode => $allowedActions) {
            $permissions = $modulePermissions[$moduleCode] ?? [];

            $filtered = array_filter(
                $permissions,
                fn(Permission $permission) => in_array(
                    $permission->action,
                    $allowedActions,
                    true,
                ),
            );

            $this->assignPermissionsToRole($role, $filtered);
        }
    }

    private function assignPermissionsToRole(Role $role, array $permissions): void
    {
        $pivotData = [];

        foreach ($permissions as $permission) {
            $pivotData[$permission->id] = [];
        }

        if (! empty($pivotData)) {
            $role->permissions()->syncWithoutDetaching($pivotData);
        }
    }

    private function createSuperAdmin(Role $role): void
    {
        $email = 'arkarmin@gmail.com';

        $admin = Admin::where('email', $email)->first();

        if (! $admin) {
            Admin::create([
                'email' => $email,
                'full_name' => 'Super Admin',
                'phone' => '09756192218',
                'role_id' => $role->id,
                'password' => Hash::make('passwordD123!@#'),
            ]);
        }
    }

    private function createNormalUser(): void
    {
        $email = 'kaungkaung@gmail.com';

        $user = User::where('email', $email)->first();

        if (! $user) {
            User::create([
                'email' => $email,
                'full_name' => 'Kaung Kaung',
                'phone' => '095085730',
                'user_type' => 'owner',
                'is_banned' => false,
                'password' => Hash::make('passwordD123!@#'),
            ]);
        }
    }
}
