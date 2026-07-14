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
        if ($pengajuanCuti->kelompok_kerja_id) {
            $unitKerja = $pengajuanCuti->kelompokKerja?->unitKerja;
            $pengajuanCuti->unit_kerja_id = $unitKerja?->id;
            $pengajuanCuti->seksi_id = $unitKerja?->seksi_id;
        } else {
            $pengajuanCuti->unit_kerja_id = $pengajuanCuti->user?->unit_kerja_id;
            $pengajuanCuti->seksi_id = $pengajuanCuti->user?->seksi_id ?? $pengajuanCuti->user?->unitKerja?->seksi_id;
        }

        $submitter = $pengajuanCuti->user;
        if ($submitter?->hasRole('kasubag')) {
            $pengajuanCuti->keputusan_kanit = 'dilewati';
            $pengajuanCuti->keputusan_kasubag = 'dilewati';
            $pengajuanCuti->status = 'menunggu_atasan';
            CutiService::handleApprovalStatus($pengajuanCuti);
        } elseif ($submitter?->hasRole('kanit')) {
            $pengajuanCuti->keputusan_kanit = 'dilewati';
        }

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

        $saldo = $pengajuanCuti->user->fresh()->saldoCuti;
        if (!$saldo)
            return;

        $lama = $pengajuanCuti->lama_cuti;
        if ($lama <= 0)
            return;

        $totalSaldo = 0;
        if ($pengajuanCuti->jenis_cuti === 'tahunan') {
            $totalSaldo = $saldo->saldo_n2 + $saldo->saldo_n1 + $saldo->saldo_n;
        }
        // elseif (in_array($pengajuanCuti->jenis_cuti, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
        //     $field = 'saldo_cuti_' . $pengajuanCuti->jenis_cuti;
        //     $totalSaldo = $saldo->{$field} ?? 0;
        // }
        $totalSaldo = CutiService::hitungSaldoTersedia($pengajuanCuti->user, $pengajuanCuti->jenis_cuti);
        if ($totalSaldo < $lama) {
            return false;
        }
    }


    public function created(PengajuanCuti $pengajuanCuti)
    {
        if ($pengajuanCuti->user->hasRole('kasubag')) {
            $pejabats = User::role('pejabat_berwenang')->get();
            foreach ($pejabats as $pejabat) {
                Notification::make()
                    ->title('Pengajuan Cuti Baru (Skip-Level)')
                    ->body('Pengajuan cuti dari ' . $pengajuanCuti->user->nama . ' langsung menunggu persetujuan final Anda (Kasubag).')
                    ->info()
                    ->sendToDatabase($pejabat);
            }
        } else {
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
        if ($pengajuanCuti->status) {
            $this->logStatus($pengajuanCuti, null, $pengajuanCuti->status, 'Pengajuan dibuat');
        }
    }


    public function updating(PengajuanCuti $pengajuanCuti)
    {
        $oldStatus = $pengajuanCuti->getOriginal('status');

        $isOwner = auth()->check() && auth()->id() === $pengajuanCuti->user_id;
        $isAdmin = auth()->check() && auth()->user()->hasRole(['super_admin', 'admin']);

        if (($isOwner || $isAdmin) && in_array($oldStatus, ['perubahan', 'ditangguhkan']) && !$pengajuanCuti->isDirty(['keputusan_kanit', 'keputusan_kasubag', 'keputusan_pejabat'])) {
            $submitter = $pengajuanCuti->user;
            $kanitValue = $submitter->hasRole(['kanit', 'kasubag']) ? 'dilewati' : null;
            $kasubagValue = $submitter->hasRole('kasubag') ? 'dilewati' : null;

            if ($submitter->hasRole('kasubag')) {
                $pengajuanCuti->status = 'menunggu_pejabat';
            } else {
                $pengajuanCuti->status = 'menunggu_atasan';
            }

            $pengajuanCuti->keputusan_kanit = $kanitValue;
            $pengajuanCuti->keputusan_kasubag = $kasubagValue;
            $pengajuanCuti->keputusan_pejabat = null;
            $pengajuanCuti->alasan_kanit = null;
            $pengajuanCuti->alasan_kasubag = null;
            $pengajuanCuti->alasan_pejabat = null;

            if (!$submitter->hasRole('kasubag')) {
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
        }

        if ($pengajuanCuti->isDirty(['keputusan_kanit', 'keputusan_kasubag', 'keputusan_pejabat'])) {
            CutiService::handleApprovalStatus($pengajuanCuti);
            if (in_array($pengajuanCuti->status, ['ditolak_kanit', 'ditolak_kasubag', 'ditolak_pejabat', 'perubahan'])) {
                CutiService::releaseSaldo($pengajuanCuti);
            }
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
                $body = 'Status pengajuan cuti Anda menjadi: ' . str_replace('_', ' ', strtoupper($pengajuanCuti->status));
                
                if (in_array($pengajuanCuti->status, ['ditolak_kanit', 'ditolak_kasubag', 'ditolak_pejabat', 'perubahan'])) {
                    $reason = $pengajuanCuti->alasan_pejabat ?? $pengajuanCuti->alasan_kasubag ?? $pengajuanCuti->alasan_kanit;
                    if ($reason) {
                        $body .= " (Alasan: " . $reason . ")";
                    }
                }

                Notification::make()
                    ->title('Status Pengajuan Cuti Berubah')
                    ->body($body)
                    ->info()
                    ->sendToDatabase($pengajuanCuti->user);
            }
        }

        if ($pengajuanCuti->isDirty(['tanggal_mulai', 'tanggal_selesai', 'kelompok_kerja_id'])) {
            $lamaLama = $pengajuanCuti->getOriginal('lama_cuti');
            $lamaBaru = CutiService::hitungLamaCuti(
                Carbon::parse($pengajuanCuti->tanggal_mulai),
                Carbon::parse($pengajuanCuti->tanggal_selesai),
                $pengajuanCuti->user->unitKerja ?? null,
                $pengajuanCuti->kelompokKerja ?? null
            );
            $pengajuanCuti->lama_cuti = $lamaBaru;

            $isResubmit = in_array($oldStatus, ['perubahan', 'ditangguhkan']) && $pengajuanCuti->status === 'menunggu_atasan';
            
            if (!$isResubmit && $lamaLama !== null && $lamaLama !== $lamaBaru) {
                CutiService::koreksiSaldo($pengajuanCuti, $lamaLama, $lamaBaru);

                Notification::make()
                    ->title('Koreksi Data Administratif')
                    ->body('Tanggal pada pengajuan cuti Anda telah disesuaikan secara administratif. Saldo Anda telah dikoreksi menyesuaikan perubahan hari cuti.')
                    ->info()
                    ->sendToDatabase($pengajuanCuti->user);
            }
        }
        if ($pengajuanCuti->wasChanged('status')) {
            $oldStatus = $pengajuanCuti->getOriginal('status');
            $this->logStatus($pengajuanCuti, $oldStatus, $pengajuanCuti->status);
        }
    }

    public function deleting(PengajuanCuti $pengajuanCuti)
    {
        CutiService::releaseSaldo($pengajuanCuti);
        if ($pengajuanCuti->isForceDeleting())
            return;

        $this->logStatus($pengajuanCuti, $pengajuanCuti->status, 'dihapus', 'Pengajuan dihapus oleh admin');
    }

    private function logStatus(PengajuanCuti $pengajuanCuti, ?string $from, string $to, ?string $keterangan = null): void
    {
        if (!$keterangan) {
            $keterangan = $this->getStatusChangeDescription($pengajuanCuti, $to);
        }

        $pengajuanCuti->statusLogs()->create([
            'status_from' => $from,
            'status_to' => $to,
            'changed_by' => auth()->check() ? auth()->id() : $pengajuanCuti->user_id,
            'keterangan' => $keterangan,
        ]);
    }

    private function getStatusChangeDescription(PengajuanCuti $pengajuanCuti, string $newStatus): string
    {
        if ($newStatus === 'ditolak_kanit') {
            return 'Ditolak oleh Kanit' . ($pengajuanCuti->alasan_kanit ? ': ' . $pengajuanCuti->alasan_kanit : '');
        }
        if ($newStatus === 'ditolak_kasubag') {
            return 'Ditolak oleh Kasubag' . ($pengajuanCuti->alasan_kasubag ? ': ' . $pengajuanCuti->alasan_kasubag : '');
        }
        if ($newStatus === 'ditolak_pejabat') {
            return 'Ditolak oleh Pejabat Berwenang' . ($pengajuanCuti->alasan_pejabat ? ': ' . $pengajuanCuti->alasan_pejabat : '');
        }
        if ($newStatus === 'perubahan') {
            $alasan = $pengajuanCuti->alasan_kanit ?? $pengajuanCuti->alasan_kasubag ?? $pengajuanCuti->alasan_pejabat;
            return 'Diminta perubahan' . ($alasan ? ' oleh approver: ' . $alasan : '');
        }
        if ($newStatus === 'disetujui') {
            return 'Disetujui oleh Pejabat Berwenang';
        }
        if (in_array($newStatus, ['menunggu_atasan', 'menunggu_pejabat'])) {
            return 'Pengajuan dikirim ulang oleh pemohon';
        }
        return 'Status berubah menjadi ' . str_replace('_', ' ', $newStatus);   
    }
}
