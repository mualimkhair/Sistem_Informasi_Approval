<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_all_pegawai');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('view_all_pegawai');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_pegawai');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage_pegawai');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('manage_pegawai');
    }

    public function editSaldo(User $user): bool
    {
        return $user->hasPermissionTo('edit_saldo_cuti');
    }

    public function resetSaldo(User $user): bool
    {
        return $user->hasPermissionTo('reset_saldo_cuti');
    }
}
