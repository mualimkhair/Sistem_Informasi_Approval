<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy(\App\Observers\PengajuanCutiObserver::class)]
class PengajuanCuti extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function kelompokKerja()
    {
        return $this->belongsTo(KelompokKerja::class);
    }

    public function seksi()
    {
        return $this->belongsTo(Seksi::class);
    }

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }

    public function ledgers() 
    {
        return $this->hasMany(SaldoCutiLedger::class, 'pengajuan_cuti_id', 'id');
    }

    public function scopeForApprover($query, $user)
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return $query;
        }

        return $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($user) {
            $q->where('user_id', $user->id);

            if ($user->hasRole('pejabat_berwenang')) {
                $q->orWhere(function(\Illuminate\Database\Eloquent\Builder $q2) {
                    $q2->where('keputusan_kanit', 'disetujui')
                       ->where('keputusan_kasubag', 'disetujui');
                });
            }

            if ($user->hasRole('kasubag')) {
                // By snapshot
                $q->orWhereHas('seksi', fn(\Illuminate\Database\Eloquent\Builder $q2) => $q2->where('kepala_seksi_id', $user->id));
                $q->orWhereHas('unitKerja', fn(\Illuminate\Database\Eloquent\Builder $q2) =>
                    $q2->whereHas('seksi', fn(\Illuminate\Database\Eloquent\Builder $q3) => $q3->where('kepala_seksi_id', $user->id))
                );
                
                // Fallback by current profile (if snapshot is null)
                $q->orWhere(function(\Illuminate\Database\Eloquent\Builder $q2) use ($user) {
                    $q2->whereNull('seksi_id')
                       ->whereHas('user', function(\Illuminate\Database\Eloquent\Builder $q3) use ($user) {
                           $q3->whereHas('seksi', fn(\Illuminate\Database\Eloquent\Builder $q4) => $q4->where('kepala_seksi_id', $user->id))
                              ->orWhereHas('unitKerja', fn(\Illuminate\Database\Eloquent\Builder $q4) =>
                                  $q4->whereHas('seksi', fn(\Illuminate\Database\Eloquent\Builder $q5) => $q5->where('kepala_seksi_id', $user->id))
                              );
                       });
                });
            }

            if ($user->hasRole('kanit')) {
                // By snapshot
                $q->orWhereHas('unitKerja', fn(\Illuminate\Database\Eloquent\Builder $q2) => $q2->where('kepala_unit_id', $user->id));
                
                // Fallback by current profile (if snapshot is null)
                $q->orWhere(function(\Illuminate\Database\Eloquent\Builder $q2) use ($user) {
                    $q2->whereNull('unit_kerja_id')
                       ->whereHas('user', function(\Illuminate\Database\Eloquent\Builder $q3) use ($user) {
                           $q3->whereHas('unitKerja', fn(\Illuminate\Database\Eloquent\Builder $q4) => $q4->where('kepala_unit_id', $user->id));
                       });
                });
            }
        });
    }
}
