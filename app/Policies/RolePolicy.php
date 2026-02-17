<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Role;

class RolePolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermission('ROLES', 'READ');
    }

    public function view(Admin $admin, Role $role): bool
    {
        return $admin->hasPermission('ROLES', 'READ');
    }

    public function create(Admin $admin): bool
    {
        return $admin->hasPermission('ROLES', 'CREATE');
    }

    public function update(Admin $admin, Role $role): bool
    {
        return $admin->hasPermission('ROLES', 'UPDATE');
    }

    public function delete(Admin $admin, Role $role): bool
    {
        return $admin->hasPermission('ROLES', 'DELETE');
    }

    public function deleteAny(Admin $admin): bool
    {
        return $admin->hasPermission('ROLES', 'DELETE');
    }

    public function restore(Admin $admin, Role $role): bool
    {
        return $admin->hasPermission('ROLES', 'UPDATE');
    }

    public function restoreAny(Admin $admin): bool
    {
        return $admin->hasPermission('ROLES', 'UPDATE');
    }

    public function forceDelete(Admin $admin, Role $role): bool
    {
        return $admin->hasPermission('ROLES', 'DELETE');
    }

    public function forceDeleteAny(Admin $admin): bool
    {
        return $admin->hasPermission('ROLES', 'DELETE');
    }
}

