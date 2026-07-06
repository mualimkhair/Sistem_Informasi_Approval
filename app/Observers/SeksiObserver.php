<?php

namespace App\Observers;

use App\Models\Seksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SeksiObserver
{
    public function saved(Seksi $seksi): void
    {
        if ($seksi->isDirty('kepala_seksi_id')) {
            $oldKepalaId = $seksi->getOriginal('kepala_seksi_id');
            $newKepalaId = $seksi->kepala_seksi_id;

            DB::transaction(function () use ($seksi, $oldKepalaId, $newKepalaId) {
                if ($oldKepalaId) {
                    $oldUser = User::find($oldKepalaId);
                    if ($oldUser) {
                        $isHeadOfOther = Seksi::where('kepala_seksi_id', $oldKepalaId)
                            ->where('id', '!=', $seksi->id)
                            ->exists();
                            
                        if (!$isHeadOfOther) {
                            $oldUser->removeRole('kasubag');
                        }
                    }
                }

                if ($newKepalaId) {
                    $newUser = User::find($newKepalaId);
                    if ($newUser) {
                        $newUser->assignRole('kasubag');
                        
                        if ($newUser->seksi_id !== $seksi->id) {
                            $newUser->seksi_id = $seksi->id;
                            $newUser->saveQuietly();
                        }
                    }
                }
            });
        }
    }
}
