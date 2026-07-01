<?php

namespace App\Services;

use App\Models\HariLibur;
use App\Models\KelompokKerja;
use App\Models\PengajuanCuti;
use App\Models\UnitKerja;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CutiService
{
    public static function invalidDates(Carbon $start, Carbon $end, ?UnitKerja $unitKerja, ?KelompokKerja $kelompokKerja): array
    {
        if ($end->lt($start)) {
            return [];
        }

        $holidayDates = HariLibur::query()
            ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->mapWithKeys(fn ($holiday) => [Carbon::parse($holiday->tanggal)->format('Y-m-d') => $holiday->keterangan])
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
            ->map(fn (string $value): string => substr($value, 0, 10))
            ->toArray();

        $days = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            if (! in_array($date->format('Y-m-d'), $invalidDates, true)) {
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
            if ($pengajuan->keputusan_kanit === 'tidak_disetujui') $pengajuan->status = 'ditolak_kanit';
            elseif ($pengajuan->keputusan_kanit === 'ditangguhkan') $pengajuan->status = 'ditangguhkan';
            else $pengajuan->status = 'perubahan';
        }
        elseif (in_array($pengajuan->keputusan_kasubag, ['tidak_disetujui', 'ditangguhkan', 'perubahan'])) {
            if ($pengajuan->keputusan_kasubag === 'tidak_disetujui') $pengajuan->status = 'ditolak_kasubag';
            elseif ($pengajuan->keputusan_kasubag === 'ditangguhkan') $pengajuan->status = 'ditangguhkan';
            else $pengajuan->status = 'perubahan';
        }
        elseif ($pengajuan->keputusan_kanit === 'disetujui' && $pengajuan->keputusan_kasubag === 'disetujui') {
            if ($pengajuan->status === 'menunggu_atasan') {
                $pengajuan->status = 'menunggu_pejabat';
            }
        }

        if ($pengajuan->status === 'menunggu_pejabat' && $pengajuan->keputusan_pejabat) {
            if ($pengajuan->keputusan_pejabat === 'disetujui') {
                $pengajuan->status = 'disetujui';
                self::potongSaldo($pengajuan);
            } else {
                if ($pengajuan->keputusan_pejabat === 'tidak_disetujui') $pengajuan->status = 'ditolak_pejabat';
                elseif ($pengajuan->keputusan_pejabat === 'ditangguhkan') $pengajuan->status = 'ditangguhkan';
                else $pengajuan->status = 'perubahan';
            }
        }
    }

    public static function potongSaldo(PengajuanCuti $pengajuan)
    {
        $saldo = $pengajuan->user->saldoCuti;
        if (!$saldo) return;

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

    public static function kembalikanSaldo(PengajuanCuti $pengajuan)
    {
        $saldo = $pengajuan->user->saldoCuti;
        if (!$saldo) return;

        $lama = $pengajuan->lama_cuti;
        $jenis = $pengajuan->jenis_cuti;

        if ($jenis === 'tahunan') {
            $saldo->saldo_n += $lama;
        } else {
            $field = 'saldo_cuti_' . $jenis;
            if (in_array($jenis, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
                $saldo->{$field} += $lama;
            }
        }

        $saldo->save();
    }
}
