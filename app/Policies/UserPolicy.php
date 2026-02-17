<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class UserPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermission('USERS', 'READ');
    }

    public function view(Admin $admin, User $user): bool
    {
        return $admin->hasPermission('USERS', 'READ');
    }

    public function create(Admin $admin): bool
    {
        return $admin->hasPermission('USERS', 'CREATE');
    }

    public function update(Admin $admin, User $user): bool
    {
        return $admin->hasPermission('USERS', 'UPDATE');
    }

    public function delete(Admin $admin, User $user): bool
    {
        return $admin->hasPermission('USERS', 'DELETE');
    }

    public function deleteAny(Admin $admin): bool
    {
        return $admin->hasPermission('USERS', 'DELETE');
    }

    public function restore(Admin $admin, User $user): bool
    {
        return $admin->hasPermission('USERS', 'UPDATE');
    }

    public function restoreAny(Admin $admin): bool
    {
        return $admin->hasPermission('USERS', 'UPDATE');
    }

    public function forceDelete(Admin $admin, User $user): bool
    {
        return $admin->hasPermission('USERS', 'DELETE');
    }

    public function forceDeleteAny(Admin $admin): bool
    {
        return $admin->hasPermission('USERS', 'DELETE');
    }
}

