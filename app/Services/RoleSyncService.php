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
            if ($user->hasRole('kanit')) {
                if ($user->unit_kerja_id) {
                    $unit = UnitKerja::find($user->unit_kerja_id);
                    if ($unit && $unit->kepala_unit_id !== $user->id) {
                        // Mencegah unique constraint violation: lepaskan user dari jabatan Kepala Unit di unit lain
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

                        // Cabut role kanit dari pejabat lama jika ada
                        if ($oldKepalaId) {
                            $oldUser = User::find($oldKepalaId);
                            if ($oldUser) {
                                $isHeadOfOther = UnitKerja::where('kepala_unit_id', $oldKepalaId)
                                    ->where('id', '!=', $unit->id)
                                    ->exists();
                                if (!$isHeadOfOther) {
                                    $oldUser->removeRole('kanit');
                                    $messages[] = "• Role Kanit dicabut dari '{$oldUser->nama}' (digantikan {$user->nama} di '{$unit->nama_unit}')";
                                }
                            }
                        }
                    }
                }
            } else {
                // Jika user tidak lagi punya role kanit, hapus dari semua jabatan kepala unit
                $units = UnitKerja::where('kepala_unit_id', $user->id)->get();
                foreach ($units as $u) {
                    $u->kepala_unit_id = null;
                    $u->saveQuietly();
                }
            }

            // --- KASUBAG / SEKSI ---
            if ($user->hasRole('kasubag')) {
                if ($user->seksi_id) {
                    $seksi = Seksi::find($user->seksi_id);
                    if ($seksi && $seksi->kepala_seksi_id !== $user->id) {
                        // Mencegah unique constraint violation: lepaskan user dari jabatan Kepala Seksi di seksi lain
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

                        // Cabut role kasubag dari pejabat lama jika ada
                        if ($oldKepalaId) {
                            $oldUser = User::find($oldKepalaId);
                            if ($oldUser) {
                                $isHeadOfOther = Seksi::where('kepala_seksi_id', $oldKepalaId)
                                    ->where('id', '!=', $seksi->id)
                                    ->exists();
                                if (!$isHeadOfOther) {
                                    $oldUser->removeRole('kasubag');
                                    $messages[] = "• Role Kasubag dicabut dari '{$oldUser->nama}' (digantikan {$user->nama} di '{$seksi->nama_seksi}')";
                                }
                            }
                        }
                    }
                }
            } else {
                // Jika user tidak lagi punya role kasubag, hapus dari semua jabatan kepala seksi
                $seksis = Seksi::where('kepala_seksi_id', $user->id)->get();
                foreach ($seksis as $s) {
                    $s->kepala_seksi_id = null;
                    $s->saveQuietly();
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
