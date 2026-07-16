<?php

namespace App\Services;

use App\Models\HariLibur;
use App\Models\KelompokKerja;
use App\Models\PengajuanCuti;
use App\Models\UnitKerja;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\SaldoCutiLedger;
use App\Models\User;
use App\Models\SaldoCuti;

class CutiService
{
    public static function validasiTanggal(Carbon $tanggal, string $konteks): bool
    {
        return match ($konteks) {
            'create' => $tanggal->startOfDay()->gte(Carbon::today()),
            'edit_pegawai' => $tanggal->startOfDay()->gte(Carbon::today()),
            'koreksi_admin' => $tanggal->startOfDay()->gte(Carbon::parse('2000-01-01')),
            default => throw new \InvalidArgumentException("Konteks validasi tidak dikenal: {$konteks}"),
        };
    }

    public static function invalidDates(Carbon $start, Carbon $end, ?UnitKerja $unitKerja, ?KelompokKerja $kelompokKerja): array
    {
        if ($end->lt($start)) {
            return [];
        }

        $holidayDates = HariLibur::query()
            ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->pluck('keterangan', 'tanggal')
            ->toArray();

        $invalid = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dateString = $date->format('Y-m-d');

            if (isset($holidayDates[$dateString])) {
                $invalid[] = $dateString . ' (' . $holidayDates[$dateString] . ')';
                continue;
            }

            if ($unitKerja && $unitKerja->jenis === 'operasional' && $kelompokKerja) {
                $dayName = self::translateDay($date->dayName);
                if ($dayName === $kelompokKerja->hari_libur_1 || $dayName === $kelompokKerja->hari_libur_2) {
                    $invalid[] = $dateString . ' (Hari Libur Mingguan Operasional)';
                }
            } else {
                if ($date->isWeekend()) {
                    $invalid[] = $dateString . ' (Akhir Pekan)';
                }
            }
        }

        return $invalid;
    }

    public static function hitungLamaCuti(Carbon $start, Carbon $end, ?UnitKerja $unitKerja, ?KelompokKerja $kelompokKerja): int
    {
        if ($end->lt($start)) {
            return 0;
        }

        $invalidDates = collect(self::invalidDates($start, $end, $unitKerja, $kelompokKerja))
            ->map(fn(string $value): string => substr($value, 0, 10))
            ->toArray();

        $days = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (!in_array($date->format('Y-m-d'), $invalidDates, true)) {
                $days++;
            }
        }

        return $days;
    }

    private static function translateDay(string $day): string
    {
        $map = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];
        return $map[$day] ?? $day;
    }

    public static function handleApprovalStatus(PengajuanCuti $pengajuan)
    {
        if (in_array($pengajuan->keputusan_kanit, ['tidak_disetujui', 'ditangguhkan', 'perubahan'])) {
            if ($pengajuan->keputusan_kanit === 'tidak_disetujui')
                $pengajuan->status = 'ditolak_kanit';
            elseif ($pengajuan->keputusan_kanit === 'ditangguhkan')
                $pengajuan->status = 'ditangguhkan';
            else
                $pengajuan->status = 'perubahan';
        } elseif (in_array($pengajuan->keputusan_kasubag, ['tidak_disetujui', 'ditangguhkan', 'perubahan'])) {
            if ($pengajuan->keputusan_kasubag === 'tidak_disetujui')
                $pengajuan->status = 'ditolak_kasubag';
            elseif ($pengajuan->keputusan_kasubag === 'ditangguhkan')
                $pengajuan->status = 'ditangguhkan';
            else
                $pengajuan->status = 'perubahan';
        } elseif (
            in_array($pengajuan->keputusan_kanit, ['disetujui', 'dilewati']) &&
            in_array($pengajuan->keputusan_kasubag, ['disetujui', 'dilewati'])
        ) {
            if ($pengajuan->status === 'menunggu_atasan') {
                $pengajuan->status = 'menunggu_pejabat';
            }
        }

        if ($pengajuan->status === 'menunggu_pejabat' && $pengajuan->keputusan_pejabat) {
            if ($pengajuan->keputusan_pejabat === 'disetujui') {
                $pengajuan->status = 'disetujui';
                self::potongSaldo($pengajuan);
            } else {
                if ($pengajuan->keputusan_pejabat === 'tidak_disetujui')
                    $pengajuan->status = 'ditolak_pejabat';
                elseif ($pengajuan->keputusan_pejabat === 'ditangguhkan')
                    $pengajuan->status = 'ditangguhkan';
                else
                    $pengajuan->status = 'perubahan';
            }
        }
    }

    public static function potongSaldo(PengajuanCuti $pengajuan)
    {
        $saldo = $pengajuan->user->fresh()->saldoCuti;
        if (!$saldo)
            return;

        $lama = $pengajuan->lama_cuti;
        $jenis = $pengajuan->jenis_cuti;

        if ($jenis === 'tahunan') {
            if ($saldo->saldo_n2 >= $lama) {
                $saldo->saldo_n2 -= $lama;
                $lama = 0;
            } else {
                $lama -= $saldo->saldo_n2;
                $saldo->saldo_n2 = 0;
            }

            if ($lama > 0) {
                if ($saldo->saldo_n1 >= $lama) {
                    $saldo->saldo_n1 -= $lama;
                    $lama = 0;
                } else {
                    $lama -= $saldo->saldo_n1;
                    $saldo->saldo_n1 = 0;
                }
            }

            if ($lama > 0) {
                $saldo->saldo_n -= $lama;
            }
        } else {
            $field = 'saldo_cuti_' . $jenis;
            if (in_array($jenis, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
                $saldo->{$field} -= $lama;
            }
        }

        $saldo->save();

        // Create potong ledger to close the hold
        if (
            !\App\Models\SaldoCutiLedger::where('pengajuan_cuti_id', $pengajuan->id)
                ->where('aksi', 'potong')->exists()
        ) {
            \App\Models\SaldoCutiLedger::create([
                'user_id' => $pengajuan->user_id,
                'pengajuan_cuti_id' => $pengajuan->id,
                'jenis_cuti' => $pengajuan->jenis_cuti,
                'aksi' => 'potong',
                'jumlah' => $pengajuan->lama_cuti,
                'keterangan' => 'Potong final otomatis saat disetujui (lama: ' . $pengajuan->lama_cuti . ' hari)',
            ]);
        }
    }

    public static function hitungSaldoTersedia(User $user, string $jenisCuti): int
    {
        $saldo = $user->fresh()->saldoCuti;
        if (!$saldo)
            return 0;

        $baseSaldo = self::getBaseSaldo($saldo, $jenisCuti);
        if ($baseSaldo < 0)
            return 0;

        $activeHolds = self::getActiveHoldsByJenis($user, $jenisCuti);
        return max(0, $baseSaldo - $activeHolds);
    }

    private static function getBaseSaldo($saldo, string $jenisCuti): int
    {
        if ($jenisCuti === 'tahunan') {
            return $saldo->saldo_n2 + $saldo->saldo_n1 + $saldo->saldo_n;
        }

        if (in_array($jenisCuti, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
            $field = 'saldo_cuti_' . $jenisCuti;
            return $saldo->{$field} ?? 0;
        }

        return -1; // diluar_tanggungan_negara or unknown
    }

    public static function holdSaldo(PengajuanCuti $pengajuan): void
    {
        if ($pengajuan->jenis_cuti === 'diluar_tanggungan_negara')
            return;
        if ($pengajuan->lama_cuti <= 0)
            return;

        $activeHoldCount = SaldoCutiLedger::where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'hold')
            ->sum('jumlah');
            
        $releaseCount = SaldoCutiLedger::where('pengajuan_cuti_id', $pengajuan->id)
            ->whereIn('aksi', ['release', 'potong'])
            ->sum('jumlah');

        if ($activeHoldCount > $releaseCount)
            return; // Don't double-hold

        SaldoCutiLedger::create([
            'user_id' => $pengajuan->user_id,
            'pengajuan_cuti_id' => $pengajuan->id,
            'jenis_cuti' => $pengajuan->jenis_cuti,
            'aksi' => 'hold',
            'jumlah' => $pengajuan->lama_cuti,
            'keterangan' => 'Hold otomatis saat pengajuan (lama: ' . $pengajuan->lama_cuti . ' hari)',
        ]);
    }
    public static function getActiveHoldsByJenis(User $user, string $jenisCuti): int
    {
        $totalHold = SaldoCutiLedger::where('user_id', $user->id)
            ->where('jenis_cuti', $jenisCuti)
            ->where('aksi', 'hold')
            ->sum('jumlah');

        $totalRelease = SaldoCutiLedger::where('user_id', $user->id)
            ->where('jenis_cuti', $jenisCuti)
            ->whereIn('aksi', ['release', 'potong'])
            ->sum('jumlah');

        return max(0, $totalHold - $totalRelease);
    }

    public static function releaseSaldo(PengajuanCuti $pengajuan): void
    {
        $activeHoldCount = SaldoCutiLedger::where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'hold')
            ->sum('jumlah');
            
        $releaseCount = SaldoCutiLedger::where('pengajuan_cuti_id', $pengajuan->id)
            ->whereIn('aksi', ['release', 'potong'])
            ->sum('jumlah');

        if ($activeHoldCount <= $releaseCount)
            return; 

        SaldoCutiLedger::create([
            'user_id' => $pengajuan->user_id,
            'pengajuan_cuti_id' => $pengajuan->id,
            'jenis_cuti' => $pengajuan->jenis_cuti,
            'aksi' => 'release',
            'jumlah' => $pengajuan->lama_cuti,
            'keterangan' => 'Release hold saat pengajuan (lama: ' . $pengajuan->lama_cuti . ' hari)',
        ]);
    }

    public static function koreksiSaldo(PengajuanCuti $pengajuan, int $lamaLama, int $lamaBaru): void
    {
        $selisih = $lamaBaru - $lamaLama;
        if ($selisih === 0) return;

        if ($pengajuan->status === 'disetujui') {
            $saldo = $pengajuan->user->fresh()->saldoCuti;
            if (!$saldo) return;

            if ($selisih > 0) {
                $tersedia = self::hitungSaldoTersedia($pengajuan->user, $pengajuan->jenis_cuti);
                if ($tersedia < $selisih) {
                    throw new \Exception("Saldo cuti tidak mencukupi untuk penambahan hari.");
                }

                $sisaPotong = $selisih;
                if ($pengajuan->jenis_cuti === 'tahunan') {
                    if ($saldo->saldo_n2 >= $sisaPotong) {
                        $saldo->saldo_n2 -= $sisaPotong;
                        $sisaPotong = 0;
                    } else {
                        $sisaPotong -= $saldo->saldo_n2;
                        $saldo->saldo_n2 = 0;
                    }

                    if ($sisaPotong > 0) {
                        if ($saldo->saldo_n1 >= $sisaPotong) {
                            $saldo->saldo_n1 -= $sisaPotong;
                            $sisaPotong = 0;
                        } else {
                            $sisaPotong -= $saldo->saldo_n1;
                            $saldo->saldo_n1 = 0;
                        }
                    }

                    if ($sisaPotong > 0) {
                        $saldo->saldo_n -= $sisaPotong;
                    }
                } else {
                    $field = 'saldo_cuti_' . $pengajuan->jenis_cuti;
                    if (in_array($pengajuan->jenis_cuti, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
                        $saldo->{$field} -= $sisaPotong;
                    }
                }
                $saldo->save();
            } else {
                $refund = abs($selisih);
                if ($pengajuan->jenis_cuti === 'tahunan') {
                    if ($saldo->saldo_n < 12) {
                        $kurang = 12 - $saldo->saldo_n;
                        $tambah = min($kurang, $refund);
                        $saldo->saldo_n += $tambah;
                        $refund -= $tambah;
                    }
                    if ($refund > 0 && $saldo->saldo_n1 < 6) {
                        $kurang = 6 - $saldo->saldo_n1;
                        $tambah = min($kurang, $refund);
                        $saldo->saldo_n1 += $tambah;
                        $refund -= $tambah;
                    }
                    if ($refund > 0 && $saldo->saldo_n2 < 6) {
                        $kurang = 6 - $saldo->saldo_n2;
                        $tambah = min($kurang, $refund);
                        $saldo->saldo_n2 += $tambah;
                        $refund -= $tambah;
                    }
                    if ($refund > 0) {
                        $saldo->saldo_n2 += $refund;
                    }
                } else {
                    $field = 'saldo_cuti_' . $pengajuan->jenis_cuti;
                    if (in_array($pengajuan->jenis_cuti, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
                        $saldo->{$field} += $refund;
                    }
                }
                $saldo->save();
            }

            SaldoCutiLedger::create([
                'user_id' => $pengajuan->user_id,
                'pengajuan_cuti_id' => $pengajuan->id,
                'jenis_cuti' => $pengajuan->jenis_cuti,
                'aksi' => 'koreksi',
                'jumlah' => $selisih,
                'keterangan' => 'Koreksi Admin (Disetujui): ' . $lamaLama . ' menjadi ' . $lamaBaru . ' hari',
            ]);

        } else {
            if (in_array($pengajuan->status, ['ditolak_kanit', 'ditolak_kasubag', 'ditolak_pejabat', 'ditangguhkan', 'perubahan'])) {
                return; 
            }

            if ($selisih > 0) {
                $tersedia = self::hitungSaldoTersedia($pengajuan->user, $pengajuan->jenis_cuti);
                if ($tersedia < $selisih) {
                    throw new \Exception("Saldo cuti tidak mencukupi untuk penambahan hari.");
                }
                SaldoCutiLedger::create([
                    'user_id' => $pengajuan->user_id,
                    'pengajuan_cuti_id' => $pengajuan->id,
                    'jenis_cuti' => $pengajuan->jenis_cuti,
                    'aksi' => 'hold',
                    'jumlah' => $selisih,
                    'keterangan' => 'Koreksi Admin (Hold Tambahan): ' . $lamaLama . ' menjadi ' . $lamaBaru . ' hari',
                ]);
            } else {
                SaldoCutiLedger::create([
                    'user_id' => $pengajuan->user_id,
                    'pengajuan_cuti_id' => $pengajuan->id,
                    'jenis_cuti' => $pengajuan->jenis_cuti,
                    'aksi' => 'release',
                    'jumlah' => abs($selisih),
                    'keterangan' => 'Koreksi Admin (Release Parsial): ' . $lamaLama . ' menjadi ' . $lamaBaru . ' hari',
                ]);
            }
        }
    }

    public static function rolloverSaldoTahunan(SaldoCuti $saldo): void
    {
        $tahunSekarang = now()->year;
        if ($saldo->last_rollover_year == $tahunSekarang) {
            return; // idempotent, sudah dijalankan tahun ini
        }

        $saldo->saldo_n2 = min($saldo->saldo_n1, 6);
        $saldo->saldo_n1 = min($saldo->saldo_n, 6);
        $saldo->saldo_n = 12;

        $saldo->saldo_cuti_besar = 90;
        $saldo->saldo_cuti_sakit = 365;
        $saldo->saldo_cuti_melahirkan = 90;
        $saldo->saldo_cuti_alasan_penting = 30;

        $saldo->tahun_berjalan = $tahunSekarang;
        $saldo->last_rollover_year = $tahunSekarang;
        $saldo->save();

        SaldoCutiLedger::create([
            'user_id' => $saldo->user_id,
            'jenis_cuti' => 'tahunan',
            'aksi' => 'rollover',
            'jumlah' => 0,
            'keterangan' => "Rollover tahunan {$tahunSekarang}: N2={$saldo->saldo_n2}, N1={$saldo->saldo_n1}, N={$saldo->saldo_n}",
        ]);
    }

    public static function resetSaldoToDefault(SaldoCuti $saldo): void
    {
        $saldo->saldo_n = 12;
        $saldo->saldo_n1 = 0;
        $saldo->saldo_n2 = 0;
        $saldo->saldo_cuti_besar = 0;
        $saldo->saldo_cuti_sakit = 0;
        $saldo->saldo_cuti_melahirkan = 0;
        $saldo->saldo_cuti_alasan_penting = 0;
        $saldo->tahun_berjalan = now()->year;
        $saldo->save();

        SaldoCutiLedger::create([
            'user_id' => $saldo->user_id,
            'jenis_cuti' => 'tahunan',
            'aksi' => 'factory_reset',
            'jumlah' => 0,
            'keterangan' => 'Factory reset saldo by admin',
        ]);
    }

}
