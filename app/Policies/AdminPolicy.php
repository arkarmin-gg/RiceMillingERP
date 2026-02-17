<?php

namespace App\Policies;

use App\Models\Admin;

class AdminPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->hasPermission('ADMINS', 'READ');
    }

    public function view(Admin $admin, Admin $record): bool
    {
        return $admin->hasPermission('ADMINS', 'READ');
    }

    public function create(Admin $admin): bool
    {
        return $admin->hasPermission('ADMINS', 'CREATE');
    }

    public function update(Admin $admin, Admin $record): bool
    {
        return $admin->hasPermission('ADMINS', 'UPDATE');
    }

    public function delete(Admin $admin, Admin $record): bool
    {
        return $admin->hasPermission('ADMINS', 'DELETE');
    }

    public function deleteAny(Admin $admin): bool
    {
        return $admin->hasPermission('ADMINS', 'DELETE');
    }

    public function restore(Admin $admin, Admin $record): bool
    {
        return $admin->hasPermission('ADMINS', 'UPDATE');
    }

    public function restoreAny(Admin $admin): bool
    {
        return $admin->hasPermission('ADMINS', 'UPDATE');
    }

    public function forceDelete(Admin $admin, Admin $record): bool
    {
        return $admin->hasPermission('ADMINS', 'DELETE');
    }

    public function forceDeleteAny(Admin $admin): bool
    {
        return $admin->hasPermission('ADMINS', 'DELETE');
    }
}

