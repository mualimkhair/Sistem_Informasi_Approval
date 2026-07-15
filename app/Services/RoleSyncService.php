<?php

namespace App\Services;

use App\Models\User;
use App\Models\UnitKerja;
use App\Models\Seksi;
use Illuminate\Support\Facades\DB;


class RoleSyncService
{
    public static function syncUserRoleAndHead(User $user): void
    {
        DB::transaction(function () use ($user) {
            $isSeksiHead = Seksi::where('kepala_seksi_id', $user->id)->exists();
            if ($isSeksiHead && !$user->hasRole('kasubag')) {
                $user->assignRole('kasubag');
            } elseif (!$isSeksiHead && $user->hasRole('kasubag')) {
                $user->removeRole('kasubag');
                $user->seksi_id = null;
                $user->saveQuietly();
            }

            $isUnitHead = UnitKerja::where('kepala_unit_id', $user->id)->exists();
            if ($isUnitHead && !$user->hasRole('kanit')) {
                $user->assignRole('kanit');
            } elseif (!$isUnitHead && $user->hasRole('kanit')) {
                $user->removeRole('kanit');
            }
        });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
