<?php

namespace App\Observers;

use App\Models\User;
use App\Models\PengajuanCuti;
use App\Services\CutiService;
use Carbon\Carbon;
use Filament\Notifications\Notification;

class PengajuanCutiObserver
{
    public function creating(PengajuanCuti $pengajuanCuti)
    {
        if ($pengajuanCuti->tanggal_mulai && $pengajuanCuti->tanggal_selesai) {
            $pengajuanCuti->lama_cuti = CutiService::hitungLamaCuti(
                Carbon::parse($pengajuanCuti->tanggal_mulai),
                Carbon::parse($pengajuanCuti->tanggal_selesai),
                $pengajuanCuti->user->unitKerja ?? null,
                $pengajuanCuti->kelompokKerja ?? null
            );
        }
        // Validasi server-side
        if ($pengajuanCuti->jenis_cuti === 'diluar_tanggungan_negara') {
            return;
        }

        $saldo = $pengajuanCuti->user->saldoCuti;
        if (!$saldo)
            return;

        $lama = $pengajuanCuti->lama_cuti;
        if ($lama <= 0)
            return;

        $totalSaldo = 0;
        if ($pengajuanCuti->jenis_cuti === 'tahunan') {
            $totalSaldo = $saldo->saldo_n2 + $saldo->saldo_n1 + $saldo->saldo_n;
        } elseif (in_array($pengajuanCuti->jenis_cuti, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
            $field = 'saldo_cuti_' . $pengajuanCuti->jenis_cuti;
            $totalSaldo = $saldo->{$field} ?? 0;
        }
    }

    public function created(PengajuanCuti $pengajuanCuti)
    {
        $kanitKasubags = User::role(['kanit', 'kasubag'])
            ->where('unit_kerja_id', $pengajuanCuti->user->unit_kerja_id)
            ->where('id', '!=', $pengajuanCuti->user_id)
            ->get();
        foreach ($kanitKasubags as $user) {
            Notification::make()
                ->title('Pengajuan Cuti Baru')
                ->body('Pengajuan cuti dari ' . $pengajuanCuti->user->nama . ' menunggu persetujuan Anda.')
                ->info()
                ->sendToDatabase($user);
        }
    }

    public function updating(PengajuanCuti $pengajuanCuti)
    {
        $oldStatus = $pengajuanCuti->getOriginal('status');

        if (auth()->check() && auth()->id() === $pengajuanCuti->user_id && $oldStatus === 'perubahan' && !$pengajuanCuti->isDirty(['keputusan_kanit', 'keputusan_kasubag', 'keputusan_pejabat'])) {
            $pengajuanCuti->status = 'menunggu_atasan';
            $pengajuanCuti->keputusan_kanit = null;
            $pengajuanCuti->keputusan_kasubag = null;
            $pengajuanCuti->keputusan_pejabat = null;
            $pengajuanCuti->alasan_kanit = null;
            $pengajuanCuti->alasan_kasubag = null;
            $pengajuanCuti->alasan_pejabat = null;

            $kanitKasubags = User::role(['kanit', 'kasubag'])
                ->where('unit_kerja_id', $pengajuanCuti->user->unit_kerja_id)
                ->where('id', '!=', $pengajuanCuti->user_id)
                ->get();
            foreach ($kanitKasubags as $user) {
                Notification::make()
                    ->title('Pengajuan Cuti Diperbarui')
                    ->body('Pengajuan cuti dari ' . $pengajuanCuti->user->nama . ' telah diperbarui dan menunggu persetujuan Anda.')
                    ->info()
                    ->sendToDatabase($user);
            }
        }

        if ($pengajuanCuti->isDirty(['keputusan_kanit', 'keputusan_kasubag', 'keputusan_pejabat'])) {
            CutiService::handleApprovalStatus($pengajuanCuti);
        }

        if ($pengajuanCuti->status !== $oldStatus) {
            if ($pengajuanCuti->status === 'menunggu_pejabat') {
                $pejabats = User::role('pejabat_berwenang')->get();
                foreach ($pejabats as $pejabat) {
                    Notification::make()
                        ->title('Pengajuan Lolos Level 1')
                        ->body('Pengajuan cuti dari ' . $pengajuanCuti->user->nama . ' menunggu persetujuan final Anda.')
                        ->warning()
                        ->sendToDatabase($pejabat);
                }
            } else {
                Notification::make()
                    ->title('Status Pengajuan Cuti Berubah')
                    ->body('Status pengajuan cuti Anda menjadi: ' . str_replace('_', ' ', strtoupper($pengajuanCuti->status)))
                    ->info()
                    ->sendToDatabase($pengajuanCuti->user);
            }
        }

        if ($pengajuanCuti->isDirty(['tanggal_mulai', 'tanggal_selesai', 'kelompok_kerja_id'])) {
            $pengajuanCuti->lama_cuti = CutiService::hitungLamaCuti(
                Carbon::parse($pengajuanCuti->tanggal_mulai),
                Carbon::parse($pengajuanCuti->tanggal_selesai),
                $pengajuanCuti->user->unitKerja ?? null,
                $pengajuanCuti->kelompokKerja ?? null
            );
        }
    }
}
