<?php

namespace App\Policies;

use App\Models\PengajuanCuti;
use App\Models\User;

class PengajuanCutiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_pengajuan_cuti');
    }

    public function view(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->id === $pengajuanCuti->user_id) {
            return true;
        }

        if ($user->hasRole('pejabat_berwenang')) {
            return $pengajuanCuti->status === 'menunggu_pejabat' 
                && $pengajuanCuti->keputusan_kanit === 'disetujui'
                && $pengajuanCuti->keputusan_kasubag === 'disetujui';
        }

        if ($user->hasRole('kanit')) {
            return $pengajuanCuti->user->unit_kerja_id === $user->unit_kerja_id
                && $pengajuanCuti->status === 'menunggu_atasan'
                && is_null($pengajuanCuti->keputusan_kanit);
        }

        if ($user->hasRole('kasubag')) {
            return $pengajuanCuti->user->unit_kerja_id === $user->unit_kerja_id
                && $pengajuanCuti->status === 'menunggu_atasan'
                && is_null($pengajuanCuti->keputusan_kasubag);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_pengajuan_cuti');
    }

    public function update(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->id === $pengajuanCuti->user_id && $pengajuanCuti->status === 'perubahan') {
            return true;
        }

        return false;
    }

    public function delete(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->id === $pengajuanCuti->user_id && $pengajuanCuti->status !== 'disetujui') {
            return true;
        }

        return false;
    }

    public function approveKanit(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        return $user->hasPermissionTo('approve_level_1_kanit')
            && $pengajuanCuti->status === 'menunggu_atasan'
            && is_null($pengajuanCuti->keputusan_kanit)
            && $pengajuanCuti->user_id !== $user->id
            && $pengajuanCuti->user->unit_kerja_id === $user->unit_kerja_id;
    }

    public function approveKasubag(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        return $user->hasPermissionTo('approve_level_1_kasubag')
            && $pengajuanCuti->status === 'menunggu_atasan'
            && is_null($pengajuanCuti->keputusan_kasubag)
            && $pengajuanCuti->user_id !== $user->id
            && $pengajuanCuti->user->unit_kerja_id === $user->unit_kerja_id;
    }

    public function approvePejabat(User $user, PengajuanCuti $pengajuanCuti): bool
    {
        return $user->hasPermissionTo('approve_level_2')
            && $pengajuanCuti->status === 'menunggu_pejabat'
            && $pengajuanCuti->user_id !== $user->id;
    }
}
