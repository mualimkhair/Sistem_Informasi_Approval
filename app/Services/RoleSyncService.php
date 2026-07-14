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
        $messages = [];

        DB::transaction(function () use ($user, &$messages) {
            $validUnitId = ($user->hasRole('kanit') && $user->unit_kerja_id) ? $user->unit_kerja_id : null;
            
            $otherUnits = UnitKerja::where('kepala_unit_id', $user->id)
                ->when($validUnitId, function ($q, $validUnitId) {
                    return $q->where('id', '!=', $validUnitId);
                })
                ->get();
                
            foreach ($otherUnits as $ou) {
                $ou->kepala_unit_id = null;
                $ou->saveQuietly();
                $messages[] = "• Posisi Kepala Unit pada '{$ou->nama_unit}' dikosongkan karena dilepas dari '{$user->nama}'";
            }

            if ($validUnitId) {
                $unit = UnitKerja::find($validUnitId);
                if ($unit && $unit->kepala_unit_id !== $user->id) {
                    $oldKepalaId = $unit->kepala_unit_id;
                    $unit->kepala_unit_id = $user->id;
                    $unit->saveQuietly();

                    if ($oldKepalaId) {
                        $oldUser = User::find($oldKepalaId);
                        if ($oldUser) {
                            $isHeadOfOther = UnitKerja::where('kepala_unit_id', $oldKepalaId)
                                ->where('id', '!=', $unit->id)
                                ->exists();
                            if (!$isHeadOfOther) {
                                $oldUser->removeRole('kanit');
                                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                                $messages[] = "• Role Kanit dicabut dari '{$oldUser->nama}' (digantikan {$user->nama} di '{$unit->nama_unit}')";
                            }
                        }
                    }
                }
            }

            $validSeksiId = ($user->hasRole('kasubag') && $user->seksi_id) ? $user->seksi_id : null;

            $otherSeksis = Seksi::where('kepala_seksi_id', $user->id)
                ->when($validSeksiId, function ($q, $validSeksiId) {
                    return $q->where('id', '!=', $validSeksiId);
                })
                ->get();

            foreach ($otherSeksis as $os) {
                $os->kepala_seksi_id = null;
                $os->saveQuietly();
                $messages[] = "• Posisi Kasubag pada '{$os->nama_seksi}' dikosongkan karena dilepas dari '{$user->nama}'";
            }

            if (!$validSeksiId && $user->seksi_id) {
                $user->seksi_id = null;
                $user->saveQuietly();
            }

            if ($validSeksiId) {
                $seksi = Seksi::find($validSeksiId);
                if ($seksi && $seksi->kepala_seksi_id !== $user->id) {
                    $oldKepalaId = $seksi->kepala_seksi_id;
                    $seksi->kepala_seksi_id = $user->id;
                    $seksi->saveQuietly();

                    if ($oldKepalaId) {
                        $oldUser = User::find($oldKepalaId);
                        if ($oldUser) {
                            $isHeadOfOther = Seksi::where('kepala_seksi_id', $oldKepalaId)
                                ->where('id', '!=', $seksi->id)
                                ->exists();
                            if (!$isHeadOfOther) {
                                $oldUser->removeRole('kasubag');
                                app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
                                $messages[] = "• Role Kasubag dicabut dari '{$oldUser->nama}' (digantikan {$user->nama} di '{$seksi->nama_seksi}')";
                            }
                        }
                    }
                }
            }
        });

        if (!empty($messages)) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Perhatian — Perubahan Struktur Otomatis')
                ->body(new \Illuminate\Support\HtmlString(implode("<br>", $messages)))
                ->send();
        }
    }
}
