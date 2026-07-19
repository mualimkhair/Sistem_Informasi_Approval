<?php

namespace App\Policies;

use App\Models\PengajuanCuti;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PengajuanCutiPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) return true;
        
        // Owner
        if ($pengajuanCuti->user_id == $user->id) return true;

        // Approvers can view it if it's from their unit (Kanit/Kasubag) or they are Pejabat
        if ($user->hasRole('pejabat_berwenang')) return true;

        if ($user->hasRole('kasubag') && $pengajuanCuti->seksi_id == $user->seksi_id) return true;
        
        if ($user->hasRole('kanit') && $pengajuanCuti->unit_kerja_id == $user->unit_kerja_id) return true;

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('pejabat_berwenang')) {
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) return true;

        // Owner can edit only if status is perubahan or ditangguhkan
        if ($pengajuanCuti->user_id == $user->id) {
            return in_array($pengajuanCuti->status, ['perubahan', 'ditangguhkan']);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) return true;

        // Owner can delete ONLY if it's not approved yet (disetujui).
        if ($pengajuanCuti->user_id == $user->id) {
            return $pengajuanCuti->status !== 'disetujui';
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
