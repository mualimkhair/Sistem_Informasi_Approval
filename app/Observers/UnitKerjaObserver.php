<?php

namespace App\Observers;

use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UnitKerjaObserver
{
    public function saved(UnitKerja $unitKerja): void
    {
        if ($unitKerja->isDirty('kepala_unit_id')) {
            $oldKepalaId = $unitKerja->getOriginal('kepala_unit_id');
            $newKepalaId = $unitKerja->kepala_unit_id;

            DB::transaction(function () use ($unitKerja, $oldKepalaId, $newKepalaId) {
                if ($oldKepalaId) {
                    $oldUser = User::find($oldKepalaId);
                    if ($oldUser) {
                        $isHeadOfOther = UnitKerja::where('kepala_unit_id', $oldKepalaId)
                            ->where('id', '!=', $unitKerja->id)
                            ->exists();
                            
                        if (!$isHeadOfOther) {
                            $oldUser->removeRole('kanit');
                        }
                    }
                }

                if ($newKepalaId) {
                    $newUser = User::find($newKepalaId);
                    if ($newUser) {
                        $newUser->assignRole('kanit');
                        
                        if ($newUser->unit_kerja_id !== $unitKerja->id) {
                            $newUser->unit_kerja_id = $unitKerja->id;
                            $newUser->saveQuietly();
                        }
                    }
                }
            });
        }
    }
}
