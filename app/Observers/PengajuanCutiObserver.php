<?php

namespace App\Observers;

use App\Models\PengajuanCuti;
use App\Models\Seksi;
use App\Models\UnitKerja;
use App\Models\User;
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
        $unitKerjaRecord = $pengajuanCuti->unit_kerja_id ? UnitKerja::find($pengajuanCuti->unit_kerja_id) : null;

        // Determine which approval flow applies based on the (snapshotted) unit's jenis
        $pengajuanCuti->tipe_aliran = ($unitKerjaRecord && $unitKerjaRecord->jenis === 'operasional')
            ? 'operasional'
            : 'administrasi';

        // Snapshot the four operasional approvers at submission time
        if ($pengajuanCuti->tipe_aliran === 'operasional') {
            $seksiRecord = $pengajuanCuti->seksi_id ? Seksi::find($pengajuanCuti->seksi_id) : null;
            $pengajuanCuti->kepala_unit_id = $unitKerjaRecord?->kepala_unit_id;
            $pengajuanCuti->kepala_seksi_id = $seksiRecord?->kepala_seksi_id;
            
            $roleKanitKepegawaian = \Spatie\Permission\Models\Role::where('name', 'kanit_kepegawaian')->first();
            $pengajuanCuti->kanit_kepegawaian_id = $roleKanitKepegawaian ? User::role($roleKanitKepegawaian)->first()?->id : null;
            
            $roleKasubagTu = \Spatie\Permission\Models\Role::where('name', 'kasubag_tu')->first();
            $pengajuanCuti->kasubag_tu_id = $roleKasubagTu ? User::role($roleKasubagTu)->first()?->id : null;

            $pengajuanCuti->status = 'menunggu_kepala_unit';

            // Skip stage 1 if the submitter is the Kepala Unit or the unit has no kepala unit
            if (
                ($unitKerjaRecord && $unitKerjaRecord->kepala_unit_id == $pengajuanCuti->user_id)
                || ($unitKerjaRecord && is_null($unitKerjaRecord->kepala_unit_id))
            ) {
                $pengajuanCuti->keputusan_kepala_unit = 'dilewati';
            }

            // Skip stage 2 if the submitter is the Kepala Seksi or the seksi has no kepala seksi
            if ($seksiRecord && (($seksiRecord->kepala_seksi_id == $pengajuanCuti->user_id) || is_null($seksiRecord->kepala_seksi_id))) {
                $pengajuanCuti->keputusan_kepala_seksi = 'dilewati';
            }

            // Skip stage 3 if no user holds the kanit_kepegawaian role (no one can approve it)
            if (is_null($pengajuanCuti->kanit_kepegawaian_id)) {
                $pengajuanCuti->keputusan_kanit_kepegawaian = 'dilewati';
            }

            // Skip stage 4 if no user holds the kasubag_tu role (final stage, auto-approve)
            if (is_null($pengajuanCuti->kasubag_tu_id)) {
                $pengajuanCuti->keputusan_kasubag_tu = 'dilewati';
            }

            // Compute lama_cuti first so any final-approval deduction (potongSaldo)
            // during promotion uses the correct duration.
            if ($pengajuanCuti->tanggal_mulai && $pengajuanCuti->tanggal_selesai) {
                $pengajuanCuti->lama_cuti = CutiService::hitungLamaCuti(
                    Carbon::parse($pengajuanCuti->tanggal_mulai),
                    Carbon::parse($pengajuanCuti->tanggal_selesai),
                    $pengajuanCuti->user->unitKerja ?? null,
                    $pengajuanCuti->kelompokKerja ?? null
                );
            }

            // Promote through any stages that are already 'dilewati'
            CutiService::handleApprovalStatus($pengajuanCuti);
        } elseif ($submitter?->hasRole('kasubag')) {
            $pengajuanCuti->keputusan_kanit = 'dilewati';
            $pengajuanCuti->keputusan_kasubag = 'dilewati';
            $pengajuanCuti->status = 'menunggu_atasan';
            CutiService::handleApprovalStatus($pengajuanCuti);
        } elseif ($submitter?->hasRole('kanit')) {
            $pengajuanCuti->keputusan_kanit = 'dilewati';
        } elseif ($unitKerjaRecord && is_null($unitKerjaRecord->kepala_unit_id)) {
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
        if (! $saldo) {
            return;
        }

        $lama = $pengajuanCuti->lama_cuti;
        if ($lama <= 0) {
            return;
        }

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
        // Handle a final auto-approval that happened during creating()
        // (e.g. all stages skipped because no approver exists) — potongSaldo
        // was deferred until the record had an id.
        if ($pengajuanCuti->status === 'disetujui') {
            CutiService::potongSaldo($pengajuanCuti);
            $this->ensureBlangkoForApproved($pengajuanCuti);
        }

        if ($pengajuanCuti->tipe_aliran === 'operasional') {
            $this->notifyOperasionalFirstApprover($pengajuanCuti);
        } else {
            $kanits = User::role('kanit')
                ->where('unit_kerja_id', $pengajuanCuti->user->unit_kerja_id)
                ->where('id', '!=', $pengajuanCuti->user_id)
                ->get();
            $seksiId = $pengajuanCuti->seksi_id ?? $pengajuanCuti->user->seksi_id;
            $kasubags = User::role('kasubag')
                ->where('seksi_id', $seksiId)
                ->where('id', '!=', $pengajuanCuti->user_id)
                ->get();

            foreach ($kanits->merge($kasubags) as $approver) {
                Notification::make()
                    ->title('Pengajuan Cuti Baru')
                    ->body('Pengajuan cuti dari '.$pengajuanCuti->user->nama.' menunggu persetujuan Anda.')
                    ->info()
                    ->sendToDatabase($approver);
            }
        }
        if ($pengajuanCuti->status) {
            $this->logStatus($pengajuanCuti, null, $pengajuanCuti->status, 'Pengajuan dibuat');
        }
    }

    public function updating(PengajuanCuti $pengajuanCuti)
    {
        $oldStatus = $pengajuanCuti->getOriginal('status');

        $isOwner = auth()->check() && auth()->id() == $pengajuanCuti->user_id;
        $isAdmin = auth()->check() && auth()->user()->hasRole(['super_admin', 'admin']);

        $isOperasional = $pengajuanCuti->tipe_aliran === 'operasional';

        $operasionalKeputusan = ['keputusan_kepala_unit', 'keputusan_kepala_seksi', 'keputusan_kanit_kepegawaian', 'keputusan_kasubag_tu'];
        $administrasiKeputusan = ['keputusan_kanit', 'keputusan_kasubag'];

        $resubmitPossible = $isOperasional
            ? ! $pengajuanCuti->isDirty(array_merge($operasionalKeputusan, $administrasiKeputusan))
            : ! $pengajuanCuti->isDirty($administrasiKeputusan);

        $isResubmitUpdate = false;

        if (($isOwner || $isAdmin) && in_array($oldStatus, ['perubahan', 'ditangguhkan']) && $resubmitPossible) {
            $isResubmitUpdate = true;
            if ($isOperasional) {
                $this->resetOperasionalForResubmit($pengajuanCuti);
            } else {
                $submitter = $pengajuanCuti->user;
                $kanitValue = $pengajuanCuti->getOriginal('keputusan_kanit') === 'dilewati' ? 'dilewati' : null;
                $kasubagValue = $pengajuanCuti->getOriginal('keputusan_kasubag') === 'dilewati' ? 'dilewati' : null;

                if ($submitter->hasRole('kasubag')) {
                    $pengajuanCuti->status = 'menunggu_atasan';
                } else {
                    $pengajuanCuti->status = 'menunggu_atasan';
                }

                $pengajuanCuti->keputusan_kanit = $kanitValue;
                $pengajuanCuti->keputusan_kasubag = $kasubagValue;
                $pengajuanCuti->alasan_kanit = null;
                $pengajuanCuti->alasan_kasubag = null;

                if (! $submitter->hasRole('kasubag')) {
                    // Only notify approvers whose stage is still open (was not skipped)
                    $rolesToNotify = [];
                    if ($kanitValue !== 'dilewati') {
                        $rolesToNotify[] = 'kanit';
                    }
                    if ($kasubagValue !== 'dilewati') {
                        $rolesToNotify[] = 'kasubag';
                    }

                    if (! empty($rolesToNotify)) {
                        $kanitKasubags = User::role($rolesToNotify)
                            ->where('unit_kerja_id', $pengajuanCuti->user->unit_kerja_id)
                            ->where('id', '!=', $pengajuanCuti->user_id)
                            ->get();
                        foreach ($kanitKasubags as $user) {
                            Notification::make()
                                ->title('Pengajuan Cuti Diperbarui')
                                ->body('Pengajuan cuti dari '.$pengajuanCuti->user->nama.' telah diperbarui dan menunggu persetujuan Anda.')
                                ->info()
                                ->sendToDatabase($user);
                        }
                    }
                }
            }
        }

        if ($isOperasional) {
            if ($pengajuanCuti->isDirty($operasionalKeputusan)) {
                CutiService::handleApprovalStatus($pengajuanCuti);
                if (in_array($pengajuanCuti->status, [
                    'ditolak_kepala_unit', 'ditolak_kepala_seksi', 'ditolak_kanit_kepegawaian',
                    'ditolak_kasubag_tu', 'perubahan',
                ])) {
                    CutiService::releaseSaldo($pengajuanCuti);
                }
            }
        } elseif ($pengajuanCuti->isDirty($administrasiKeputusan)) {
            CutiService::handleApprovalStatus($pengajuanCuti);
            if (in_array($pengajuanCuti->status, ['ditolak_kanit', 'ditolak_kasubag', 'perubahan'])) {
                CutiService::releaseSaldo($pengajuanCuti);
            }
        }

        if ($pengajuanCuti->status !== $oldStatus) {
            if ($isOperasional) {
                $this->notifyOperasionalStatusChange($pengajuanCuti);
            } else {
                $body = 'Status pengajuan cuti Anda menjadi: '.str_replace('_', ' ', strtoupper($pengajuanCuti->status));

                if (in_array($pengajuanCuti->status, ['ditolak_kanit', 'ditolak_kasubag', 'perubahan'])) {
                    $reason = $pengajuanCuti->alasan_kasubag ?? $pengajuanCuti->alasan_kanit;
                    if ($reason) {
                        $body .= ' (Alasan: '.$reason.')';
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

            $isResubmit = $isResubmitUpdate;

            if (! $isResubmit && $lamaLama !== null && $lamaLama !== $lamaBaru) {
                CutiService::koreksiSaldo($pengajuanCuti, $lamaLama, $lamaBaru);

                Notification::make()
                    ->title('Koreksi Data Administratif')
                    ->body('Tanggal pada pengajuan cuti Anda telah disesuaikan secara administratif. Saldo Anda telah dikoreksi menyesuaikan perubahan hari cuti.')
                    ->info()
                    ->sendToDatabase($pengajuanCuti->user);
            }

            if ($pengajuanCuti->isDirty(['tanggal_mulai', 'tanggal_selesai'])) {
                $oldStart = $pengajuanCuti->getOriginal('tanggal_mulai');
                $oldEnd = $pengajuanCuti->getOriginal('tanggal_selesai');

                $keterangan = sprintf(
                    'Perubahan tanggal: %s - %s menjadi %s - %s',
                    $oldStart ? Carbon::parse($oldStart)->format('d/m/Y') : '-',
                    $oldEnd ? Carbon::parse($oldEnd)->format('d/m/Y') : '-',
                    Carbon::parse($pengajuanCuti->tanggal_mulai)->format('d/m/Y'),
                    Carbon::parse($pengajuanCuti->tanggal_selesai)->format('d/m/Y')
                );

                $this->logStatus($pengajuanCuti, $pengajuanCuti->status, $pengajuanCuti->status, $keterangan);
            }

        }
        if ($pengajuanCuti->wasChanged('status')) {
            $oldStatus = $pengajuanCuti->getOriginal('status');
            $this->logStatus($pengajuanCuti, $oldStatus, $pengajuanCuti->status);
        }
    }

    public function updated(PengajuanCuti $pengajuanCuti)
    {
        // 1. status transition (already correct here)
        if ($pengajuanCuti->wasChanged('status')) {
            $this->logStatus($pengajuanCuti, $pengajuanCuti->getOriginal('status'), $pengajuanCuti->status);

            if ($pengajuanCuti->status === 'disetujui') {
                $this->ensureBlangkoForApproved($pengajuanCuti);
            }
        }

        // 2. field changes made by admin/super_admin
        $changed = $pengajuanCuti->getChanges();
        unset($changed['updated_at'], $changed['lama_cuti']);

        if (! empty($changed) && auth()->check() && auth()->user()->hasRole(['super_admin', 'admin'])) {
            $entries = [];
            foreach ($changed as $field => $new) {
                $entries[] = [
                    'field' => $field,
                    'old' => $pengajuanCuti->getOriginal($field),
                    'new' => $new,
                ];
            }
            $pengajuanCuti->auditLogs()->create([
                'user_id' => auth()->id(),   // ponytail: column is user_id, not changed_by
                'changes' => $entries,
            ]);
        }
    }

    public function deleting(PengajuanCuti $pengajuanCuti)
    {
        CutiService::releaseSaldo($pengajuanCuti);
        if ($pengajuanCuti->isForceDeleting()) {
            return;
        }

        $this->logStatus($pengajuanCuti, $pengajuanCuti->status, 'dihapus', 'Pengajuan dihapus oleh admin');
    }

    private function ensureBlangkoForApproved(PengajuanCuti $pengajuanCuti): void
    {
        $blangko = \App\Models\BlangkoCuti::firstOrCreate(
            ['pengajuan_cuti_id' => $pengajuanCuti->id],
            ['status' => 'menunggu']
        );

        if ($blangko->wasRecentlyCreated) {
            $pejabats = User::role('pejabat_berwenang')->get();
            foreach ($pejabats as $pejabat) {
                Notification::make()
                    ->title('Blangko Cuti Baru')
                    ->body('Terdapat blangko cuti baru dari '.$pengajuanCuti->user->nama.' yang menunggu persetujuan Anda.')
                    ->warning()
                    ->sendToDatabase($pejabat);
            }
        }
    }

    private function logStatus(PengajuanCuti $pengajuanCuti, ?string $from, string $to, ?string $keterangan = null): void
    {
        if (! $keterangan) {
            $keterangan = $pengajuanCuti->status_log_keterangan
                ?? $this->getStatusChangeDescription($pengajuanCuti, $to);
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
            return 'Ditolak oleh Kanit'.($pengajuanCuti->alasan_kanit ? ': '.$pengajuanCuti->alasan_kanit : '');
        }
        if ($newStatus === 'ditolak_kasubag') {
            return 'Ditolak oleh Kasubag'.($pengajuanCuti->alasan_kasubag ? ': '.$pengajuanCuti->alasan_kasubag : '');
        }
        if ($newStatus === 'perubahan') {
            $alasan = $pengajuanCuti->alasan_kanit ?? $pengajuanCuti->alasan_kasubag;

            return 'Diminta perubahan'.($alasan ? ' oleh approver: '.$alasan : '');
        }
        if ($newStatus === 'disetujui') {
            return $pengajuanCuti->tipe_aliran === 'operasional'
                ? 'Disetujui oleh Kasubag Tata Usaha'
                : 'Disetujui oleh Kanit dan Kasubag';
        }
        if ($newStatus === 'menunggu_atasan') {
            return 'Pengajuan dikirim ulang oleh pemohon';
        }
        if ($newStatus === 'ditolak_kepala_unit') {
            return 'Ditolak oleh Kepala Unit'.($pengajuanCuti->alasan_kepala_unit ? ': '.$pengajuanCuti->alasan_kepala_unit : '');
        }
        if ($newStatus === 'ditolak_kepala_seksi') {
            return 'Ditolak oleh Kepala Seksi'.($pengajuanCuti->alasan_kepala_seksi ? ': '.$pengajuanCuti->alasan_kepala_seksi : '');
        }
        if ($newStatus === 'ditolak_kanit_kepegawaian') {
            return 'Ditolak oleh Kanit Kepegawaian'.($pengajuanCuti->alasan_kanit_kepegawaian ? ': '.$pengajuanCuti->alasan_kanit_kepegawaian : '');
        }
        if ($newStatus === 'ditolak_kasubag_tu') {
            return 'Ditolak oleh Kasubag Tata Usaha'.($pengajuanCuti->alasan_kasubag_tu ? ': '.$pengajuanCuti->alasan_kasubag_tu : '');
        }
        if ($newStatus === 'menunggu_kepala_unit') {
            return 'Pengajuan menunggu persetujuan Kepala Unit';
        }
        if ($newStatus === 'menunggu_kepala_seksi') {
            return 'Pengajuan menunggu persetujuan Kepala Seksi';
        }
        if ($newStatus === 'menunggu_kanit_kepegawaian') {
            return 'Pengajuan menunggu persetujuan Kanit Kepegawaian';
        }
        if ($newStatus === 'menunggu_kasubag_tu') {
            return 'Pengajuan menunggu persetujuan Kasubag Tata Usaha';
        }

        return 'Status berubah menjadi '.str_replace('_', ' ', $newStatus);
    }

    private function notifyOperasionalFirstApprover(PengajuanCuti $pengajuanCuti): void
    {
        $waiting = [
            'menunggu_kepala_unit' => $pengajuanCuti->kepala_unit_id,
            'menunggu_kepala_seksi' => $pengajuanCuti->kepala_seksi_id,
            'menunggu_kanit_kepegawaian' => $pengajuanCuti->kanit_kepegawaian_id,
            'menunggu_kasubag_tu' => $pengajuanCuti->kasubag_tu_id,
        ];
        $approverId = $waiting[$pengajuanCuti->status] ?? null;
        if ($approverId && ($approver = User::find($approverId))) {
            Notification::make()
                ->title('Pengajuan Cuti Baru')
                ->body('Pengajuan cuti dari '.$pengajuanCuti->user->nama.' menunggu persetujuan Anda.')
                ->info()
                ->sendToDatabase($approver);
        }
    }

    private function resetOperasionalForResubmit(PengajuanCuti $pengajuanCuti): void
    {
        $kept = fn (string $field) => $pengajuanCuti->getOriginal($field) === 'dilewati' ? 'dilewati' : null;

        $pengajuanCuti->keputusan_kepala_unit = $kept('keputusan_kepala_unit');
        $pengajuanCuti->keputusan_kepala_seksi = $kept('keputusan_kepala_seksi');
        $pengajuanCuti->keputusan_kanit_kepegawaian = $kept('keputusan_kanit_kepegawaian');
        $pengajuanCuti->keputusan_kasubag_tu = $kept('keputusan_kasubag_tu');
        $pengajuanCuti->alasan_kepala_unit = null;
        $pengajuanCuti->alasan_kepala_seksi = null;
        $pengajuanCuti->alasan_kanit_kepegawaian = null;
        $pengajuanCuti->alasan_kasubag_tu = null;

        $this->resetOperasionalToFirstOpenStage($pengajuanCuti);
    }

    private function notifyOperasionalStatusChange(PengajuanCuti $pengajuanCuti): void
    {
        if (str_starts_with($pengajuanCuti->status, 'menunggu_')) {
            $this->notifyOperasionalNextApprover($pengajuanCuti);

            return;
        }

        $body = 'Status pengajuan cuti Anda menjadi: '.str_replace('_', ' ', strtoupper($pengajuanCuti->status));

        if (in_array($pengajuanCuti->status, [
            'ditolak_kepala_unit', 'ditolak_kepala_seksi', 'ditolak_kanit_kepegawaian', 'ditolak_kasubag_tu', 'perubahan',
        ])) {
            $reason = $pengajuanCuti->alasan_kasubag_tu
                ?? $pengajuanCuti->alasan_kanit_kepegawaian
                ?? $pengajuanCuti->alasan_kepala_seksi
                ?? $pengajuanCuti->alasan_kepala_unit;
            if ($reason) {
                $body .= ' (Alasan: '.$reason.')';
            }
        }

        Notification::make()
            ->title('Status Pengajuan Cuti Berubah')
            ->body($body)
            ->info()
            ->sendToDatabase($pengajuanCuti->user);
    }

    private function notifyOperasionalNextApprover(PengajuanCuti $pengajuanCuti): void
    {
        $waiting = [
            'menunggu_kepala_unit' => $pengajuanCuti->kepala_unit_id,
            'menunggu_kepala_seksi' => $pengajuanCuti->kepala_seksi_id,
            'menunggu_kanit_kepegawaian' => $pengajuanCuti->kanit_kepegawaian_id,
            'menunggu_kasubag_tu' => $pengajuanCuti->kasubag_tu_id,
        ];
        $approverId = $waiting[$pengajuanCuti->status] ?? null;
        if ($approverId && ($approver = User::find($approverId))) {
            Notification::make()
                ->title('Pengajuan Cuti Menunggu Anda')
                ->body('Pengajuan cuti dari '.$pengajuanCuti->user->nama.' kini menunggu persetujuan Anda.')
                ->info()
                ->sendToDatabase($approver);
        }
    }

    private function resetOperasionalToFirstOpenStage(PengajuanCuti $pengajuanCuti): void
    {
        $stages = [
            'menunggu_kepala_unit' => 'keputusan_kepala_unit',
            'menunggu_kepala_seksi' => 'keputusan_kepala_seksi',
            'menunggu_kanit_kepegawaian' => 'keputusan_kanit_kepegawaian',
            'menunggu_kasubag_tu' => 'keputusan_kasubag_tu',
        ];
        foreach ($stages as $status => $field) {
            // Only truly open stages (null) are candidates; 'dilewati' stays skipped
            if (is_null($pengajuanCuti->$field)) {
                $pengajuanCuti->status = $status;

                return;
            }
        }
        $pengajuanCuti->status = 'menunggu_kasubag_tu';
    }
}
