<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanCuti;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MenungguKeputusanWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $stats = [];
        $user = Auth::user();

        $isApprover = $user->hasRole(['kanit', 'kasubag', 'pejabat_berwenang', 'kanit_kepegawaian', 'kasubag_tu']);
        if (! $isApprover) {
            return $stats;
        }

        // --- Administrasi flow: kanit / kasubag (approval level 1) ---
        if ($user->hasRole(['kanit', 'kasubag'])) {
            $query = PengajuanCuti::forApprover($user)
                ->where('status', 'menunggu_atasan');

            $query->where(function ($q) use ($user) {
                if ($user->hasRole('kanit')) {
                    $q->orWhereNull('keputusan_kanit');
                }
                if ($user->hasRole('kasubag')) {
                    $q->orWhereNull('keputusan_kasubag');
                }
            });

            $menungguLevel1 = $query->count();

            $stats[] = Stat::make('Menunggu Keputusan Anda', $menungguLevel1)
                ->icon('heroicon-o-clock')
                ->color('warning');

            // --- Operasional flow: Kepala Unit (stage 1) ---
            if ($user->hasRole('kanit')) {
                $menungguOperasional = PengajuanCuti::where('tipe_aliran', 'operasional')
                    ->where('status', 'menunggu_kepala_unit')
                    ->where('kepala_unit_id', $user->id)
                    ->where('user_id', '!=', $user->id)
                    ->whereNull('keputusan_kepala_unit')
                    ->count();
                if ($menungguOperasional > 0) {
                    $stats[] = Stat::make('Menunggu Persetujuan (Kepala Unit)', $menungguOperasional)
                        ->icon('heroicon-o-clipboard-document')
                        ->color('warning');
                }
            }

            // --- Operasional flow: Kepala Seksi (stage 2) ---
            if ($user->hasRole('kasubag')) {
                $menungguOperasional = PengajuanCuti::where('tipe_aliran', 'operasional')
                    ->where('status', 'menunggu_kepala_seksi')
                    ->where('kepala_seksi_id', $user->id)
                    ->where('user_id', '!=', $user->id)
                    ->whereNull('keputusan_kepala_seksi')
                    ->count();
                if ($menungguOperasional > 0) {
                    $stats[] = Stat::make('Menunggu Persetujuan (Kepala Seksi)', $menungguOperasional)
                        ->icon('heroicon-o-clipboard-document')
                        ->color('warning');
                }
            }
        }

        // --- Operasional flow: Kanit Kepegawaian (stage 3) ---
        if ($user->hasRole('kanit_kepegawaian')) {
            $menungguStage3 = PengajuanCuti::forApprover($user)
                ->where('status', 'menunggu_kanit_kepegawaian')
                ->where('user_id', '!=', $user->id)
                ->count();
            $stats[] = Stat::make('Menunggu Keputusan Anda', $menungguStage3)
                ->icon('heroicon-o-clock')
                ->color('warning');
        }

        // --- Operasional flow: Kasubag TU (stage 4 / final) ---
        if ($user->hasRole('kasubag_tu')) {
            $kasubagTuFinal = PengajuanCuti::forApprover($user)
                ->where('status', 'menunggu_kasubag_tu')
                ->where('user_id', '!=', $user->id)
                ->count();
            $stats[] = Stat::make('Menunggu Keputusan Final', $kasubagTuFinal)
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning');
        }

        // --- Sedang cuti (visible to final approvers) ---
        if ($user->hasRole(['kasubag_tu', 'pejabat_berwenang'])) {
            $sedangCuti = PengajuanCuti::where('status', 'disetujui')
                ->where('tanggal_mulai', '<=', now())
                ->where('tanggal_selesai', '>=', now())
                ->count();
            $stats[] = Stat::make('Pegawai Sedang Cuti', $sedangCuti)
                ->icon('heroicon-o-users')
                ->color('success');
        }

        return $stats;
    }
}
