<?php

namespace App\Models;

use App\Observers\PengajuanCutiObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(PengajuanCutiObserver::class)]
class PengajuanCuti extends Model
{
    use HasUlids, SoftDeletes;

    public ?string $status_log_keterangan = null;

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

    public function blangkoCuti()
    {
        return $this->hasOne(BlangkoCuti::class);
    }

    public function getIsOperasionalAttribute()
    {
        return $this->tipe_aliran === 'operasional';
    }

    public function getKanitAttribute()
    {
        return $this->unitKerja?->kepalaUnit;
    }

    public function getKasubagAttribute()
    {
        return $this->seksi?->kepalaSeksi;
    }

    // Kepala Unit (operasional stage 1) — prefer snapshot, fall back to dynamic
    public function getKepalaUnitAttribute()
    {
        if ($this->kepala_unit_id) {
            return User::find($this->kepala_unit_id);
        }

        return $this->unitKerja?->kepalaUnit;
    }

    // Kepala Seksi (operasional stage 2) — prefer snapshot, fall back to dynamic
    public function getKepalaSeksiAttribute()
    {
        if ($this->kepala_seksi_id) {
            return User::find($this->kepala_seksi_id);
        }

        return $this->seksi?->kepalaSeksi;
    }

    // Kanit Kepegawaian (operasional stage 3) — prefer snapshot, fall back to role lookup
    public function getKanitKepegawaianAttribute()
    {
        if ($this->kanit_kepegawaian_id) {
            return User::find($this->kanit_kepegawaian_id);
        }

        $role = \Spatie\Permission\Models\Role::where('name', 'kanit_kepegawaian')->first();
        return $role ? User::role($role)->first() : null;
    }

    // Kasubag TU (operasional stage 4) — prefer snapshot, fall back to role lookup
    public function getKasubagTuAttribute()
    {
        if ($this->kasubag_tu_id) {
            return User::find($this->kasubag_tu_id);
        }

        $role = \Spatie\Permission\Models\Role::where('name', 'kasubag_tu')->first();
        return $role ? User::role($role)->first() : null;
    }

    // removed getPejabatAttribute

    public function scopeForApprover($query, $user)
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id);

            // ============ ADMINISTRASI FLOW (existing) ============

            if ($user->hasRole('kasubag')) {
                // By snapshot
                $q->orWhere(function (Builder $q2) use ($user) {
                    $q2->where('tipe_aliran', 'administrasi')
                        ->where(function (Builder $q3) use ($user) {
                            $q3->whereHas('seksi', fn (Builder $q4) => $q4->where('kepala_seksi_id', $user->id))
                                ->orWhereHas(
                                    'unitKerja',
                                    fn (Builder $q4) => $q4->whereHas('seksi', fn (Builder $q5) => $q5->where('kepala_seksi_id', $user->id))
                                );

                            // Fallback by current profile (if snapshot is null)
                            $q3->orWhere(function (Builder $q4) use ($user) {
                                $q4->whereNull('seksi_id')
                                    ->whereHas('user', fn (Builder $q5) => $q5
                                        ->whereHas('seksi', fn (Builder $q6) => $q6->where('kepala_seksi_id', $user->id))
                                        ->orWhereHas(
                                            'unitKerja',
                                            fn (Builder $q6) => $q6->whereHas('seksi', fn (Builder $q7) => $q7->where('kepala_seksi_id', $user->id))
                                        ));
                            });
                        });
                });
            }

            if ($user->hasRole('kanit')) {
                // By snapshot
                $q->orWhere(function (Builder $q2) use ($user) {
                    $q2->where('tipe_aliran', 'administrasi')
                        ->where(function (Builder $q3) use ($user) {
                            $q3->whereHas('unitKerja', fn (Builder $q4) => $q4->where('kepala_unit_id', $user->id));

                            // Fallback by current profile (if snapshot is null)
                            $q3->orWhere(function (Builder $q4) use ($user) {
                                $q4->whereNull('unit_kerja_id')
                                    ->whereHas('user', fn (Builder $q5) => $q5
                                        ->whereHas('unitKerja', fn (Builder $q6) => $q6->where('kepala_unit_id', $user->id)));
                            });
                        });
                });
            }

            // ============ OPERASIONAL FLOW (new) ============

            // Kepala Unit (stage 1) — role kanit, matched by snapshot column (fallback: current org when snapshot null)
            if ($user->hasRole('kanit')) {
                $q->orWhere(function (Builder $q2) use ($user) {
                    $q2->where('tipe_aliran', 'operasional')
                        ->where(function (Builder $q3) use ($user) {
                            // 1) Snapshot match: the approver captured at submission time
                            $q3->where('kepala_unit_id', $user->id);

                            // 2) Fallback by current profile (if snapshot is null)
                            $q3->orWhere(function (Builder $q4) use ($user) {
                                $q4->whereNull('kepala_unit_id')
                                    ->where(function (Builder $q5) use ($user) {
                                        $q5->whereHas('unitKerja', fn (Builder $q6) => $q6->where('kepala_unit_id', $user->id))
                                            ->orWhere(function (Builder $q6) use ($user) {
                                                $q6->whereNull('unit_kerja_id')
                                                    ->whereHas('user', fn (Builder $q7) => $q7
                                                        ->whereHas('unitKerja', fn (Builder $q8) => $q8->where('kepala_unit_id', $user->id)));
                                            });
                                    });
                            });
                        });
                });
            }

            // Kepala Seksi (stage 2) — role kasubag, matched by snapshot column (fallback: current org when snapshot null)
            if ($user->hasRole('kasubag')) {
                $q->orWhere(function (Builder $q2) use ($user) {
                    $q2->where('tipe_aliran', 'operasional')
                        ->where(function (Builder $q3) use ($user) {
                            // 1) Snapshot match: the approver captured at submission time
                            $q3->where('kepala_seksi_id', $user->id);

                            // 2) Fallback by current profile (if snapshot is null)
                            $q3->orWhere(function (Builder $q4) use ($user) {
                                $q4->whereNull('kepala_seksi_id')
                                    ->where(function (Builder $q5) use ($user) {
                                        $q5->whereHas('seksi', fn (Builder $q6) => $q6->where('kepala_seksi_id', $user->id))
                                            ->orWhereHas('unitKerja', fn (Builder $q6) => $q6->whereHas('seksi', fn (Builder $q7) => $q7->where('kepala_seksi_id', $user->id)))
                                            ->orWhere(function (Builder $q6) use ($user) {
                                                $q6->whereNull('seksi_id')
                                                    ->whereHas('user', fn (Builder $q7) => $q7
                                                        ->whereHas('seksi', fn (Builder $q8) => $q8->where('kepala_seksi_id', $user->id))
                                                        ->orWhereHas(
                                                            'unitKerja',
                                                            fn (Builder $q8) => $q8->whereHas('seksi', fn (Builder $q9) => $q9->where('kepala_seksi_id', $user->id))
                                                        ));
                                            });
                                    });
                            });
                        });
                });
            }

            // Kanit Kepegawaian (stage 3) — matched by snapshot of who holds the role
            if ($user->hasRole('kanit_kepegawaian')) {
                $q->orWhere(function (Builder $q2) use ($user) {
                    $q2->where('tipe_aliran', 'operasional')
                        ->where('kanit_kepegawaian_id', $user->id);
                });
            }

            // Kasubag TU (stage 4) — matched by snapshot of who holds the role
            if ($user->hasRole('kasubag_tu')) {
                $q->orWhere(function (Builder $q2) use ($user) {
                    $q2->where('tipe_aliran', 'operasional')
                        ->where('kasubag_tu_id', $user->id);
                });
            }
        });
    }

    public function statusLogs()
    {
        return $this->hasMany(PengajuanCutiStatusLog::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(PengajuanCutiAuditLog::class);
    }
}
