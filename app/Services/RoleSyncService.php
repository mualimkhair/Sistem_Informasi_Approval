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
            // --- KANIT / UNIT KERJA ---
            if ($user->hasRole('kanit') && $user->unit_kerja_id) {
                $unit = UnitKerja::find($user->unit_kerja_id);
                if ($unit && $unit->kepala_unit_id !== $user->id) {
                    $otherUnits = UnitKerja::where('kepala_unit_id', $user->id)
                        ->where('id', '!=', $unit->id)
                        ->get();
                    foreach ($otherUnits as $ou) {
                        $ou->kepala_unit_id = null;
                        $ou->saveQuietly();
                        $messages[] = "• {$user->nama} dilepas dari Kepala Unit '{$ou->nama_unit}'";
                    }

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

            // --- KASUBAG / SEKSI ---
            if ($user->hasRole('kasubag') && $user->seksi_id) {
                $seksi = Seksi::find($user->seksi_id);
                if ($seksi && $seksi->kepala_seksi_id !== $user->id) {
                    $otherSeksis = Seksi::where('kepala_seksi_id', $user->id)
                        ->where('id', '!=', $seksi->id)
                        ->get();
                    foreach ($otherSeksis as $os) {
                        $os->kepala_seksi_id = null;
                        $os->saveQuietly();
                        $messages[] = "• {$user->nama} dilepas dari Kasubag '{$os->nama_seksi}'";
                    }

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
