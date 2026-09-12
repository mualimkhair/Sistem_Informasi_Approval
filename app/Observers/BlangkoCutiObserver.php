<?php

namespace App\Observers;

use App\Models\BlangkoCuti;

class BlangkoCutiObserver
{
    public function updating(BlangkoCuti $blangkoCuti)
    {
        if ($blangkoCuti->isDirty('status')) {
            $newStatus = $blangkoCuti->status;
            if (in_array($newStatus, ['disetujui', 'ditolak'])) {
                if (is_null($blangkoCuti->kabandara_nama) && auth()->check()) {
                    $user = auth()->user();
                    $blangkoCuti->kabandara_id = $user->id;
                    $blangkoCuti->kabandara_nama = $user->nama;
                    $blangkoCuti->kabandara_nip = $user->nip;
                    $blangkoCuti->kabandara_pangkat = $user->pangkat_gol;
                    $blangkoCuti->kabandara_jabatan = $user->jabatan;
                    $blangkoCuti->tanggal_keputusan = now();
                }
            }
        }
    }
}
