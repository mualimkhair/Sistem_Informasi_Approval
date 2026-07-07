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

class CutiService
{
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
        } elseif ($pengajuan->keputusan_kanit === 'disetujui' && $pengajuan->keputusan_kasubag === 'disetujui') {
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
        $saldo = $pengajuan->user->saldoCuti;
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
    }

    public static function hitungSaldoTersedia(User $user, string $jenisCuti): int
    {
        $saldo = $user->saldoCuti;
        if (!$saldo)
            return 0;

        $baseSaldo = self::getBaseSaldo($saldo, $jenisCuti);
        if ($baseSaldo < 0)
            return 0;

        // Calculate active holds (holds that haven't been released or potong'd)
        $activeHoldPengajuanIds = SaldoCutiLedger::where('user_id', $user->id)
            ->where('jenis_cuti', $jenisCuti)
            ->where('aksi', 'hold')
            ->pluck('pengajuan_cuti_id')
            ->unique()
            ->filter();

        if ($activeHoldPengajuanIds->isNotEmpty()) {
            $releasedOrPotongIds = SaldoCutiLedger::where('user_id', $user->id)
                ->where('jenis_cuti', $jenisCuti)
                ->whereIn('aksi', ['release', 'potong'])
                ->whereIn('pengajuan_cuti_id', $activeHoldPengajuanIds)
                ->pluck('pengajuan_cuti_id')
                ->unique();

            $stillActiveIds = $activeHoldPengajuanIds->diff($releasedOrPotongIds);

            if ($stillActiveIds->isNotEmpty()) {
                $activeHolds = SaldoCutiLedger::where('user_id', $user->id)
                    ->where('jenis_cuti', $jenisCuti)
                    ->where('aksi', 'hold')
                    ->whereIn('pengajuan_cuti_id', $stillActiveIds)
                    ->sum('jumlah');

                $baseSaldo = max(0, $baseSaldo - $activeHolds);
            }
        }

        return $baseSaldo;
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

        // Check if there's already an active hold for this pengajuan
        $existingHold = SaldoCutiLedger::where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'hold')
            ->first();

        if ($existingHold)
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
        $activeHoldPengajuanIds = SaldoCutiLedger::where('user_id', $user->id)
            ->where('jenis_cuti', $jenisCuti)
            ->where('aksi', 'hold')
            ->pluck('pengajuan_cuti_id')
            ->unique()
            ->filter();

        if ($activeHoldPengajuanIds->isEmpty())
            return 0;

        $releasedOrPotongIds = SaldoCutiLedger::where('user_id', $user->id)
            ->where('jenis_cuti', $jenisCuti)
            ->whereIn('aksi', ['release', 'potong'])
            ->whereIn('pengajuan_cuti_id', $activeHoldPengajuanIds)
            ->pluck('pengajuan_cuti_id')
            ->unique();

        $stillActiveIds = $activeHoldPengajuanIds->diff($releasedOrPotongIds);

        if ($stillActiveIds->isEmpty())
            return 0;

        return SaldoCutiLedger::where('user_id', $user->id)
            ->where('jenis_cuti', $jenisCuti)
            ->where('aksi', 'hold')
            ->whereIn('pengajuan_cuti_id', $stillActiveIds)
            ->sum('jumlah');
    }

    public static function releaseSaldo(PengajuanCuti $pengajuan): void
    {
        $hold = SaldoCutiLedger::where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'hold')
            ->first();

        if (!$hold)
            return;

        if (SaldoCutiLedger::where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'release')->exists())
            return;

        SaldoCutiLedger::create([
            'user_id' => $pengajuan->user_id,
            'pengajuan_cuti_id' => $pengajuan->id,
            'jenis_cuti' => $pengajuan->jenis_cuti,
            'aksi' => 'release',
            'jumlah' => $pengajuan->lama_cuti,
            'keterangan' => 'Release hold saat pengajuan (lama: ' . $pengajuan->lama_cuti . ' hari)',
        ]);

        $hold->delete();
    }

}
