<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanCuti;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MenungguKeputusanWidget extends BaseWidget
{
    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $stats = [];
        $user = auth()->user();

        // 1. Administrasi flow: kanit / kasubag (approval level 1)
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
            if ($menungguLevel1 > 0) {
                $stats[] = Stat::make('Menunggu Keputusan Anda', $menungguLevel1)
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->url(\App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'menunggu_atasan']]]));
            }
        }

        // 2. Operasional flow: Kepala Unit (stage 1)
        $menungguKepalaUnit = PengajuanCuti::where('tipe_aliran', 'operasional')
            ->where('status', 'menunggu_kepala_unit')
            ->where('kepala_unit_id', $user->id)
            ->where('user_id', '!=', $user->id)
            ->whereNull('keputusan_kepala_unit')
            ->count();
        if ($menungguKepalaUnit > 0) {
            $stats[] = Stat::make('Menunggu Persetujuan (Kepala Unit)', $menungguKepalaUnit)
                ->icon('heroicon-o-clipboard-document')
                ->color('warning')
                ->url(\App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'menunggu_kepala_unit']]]));
        }

        // 3. Operasional flow: Kepala Seksi (stage 2)
        $menungguKepalaSeksi = PengajuanCuti::where('tipe_aliran', 'operasional')
            ->where('status', 'menunggu_kepala_seksi')
            ->where('kepala_seksi_id', $user->id)
            ->where('user_id', '!=', $user->id)
            ->whereNull('keputusan_kepala_seksi')
            ->count();
        if ($menungguKepalaSeksi > 0) {
            $stats[] = Stat::make('Menunggu Persetujuan (Kepala Seksi)', $menungguKepalaSeksi)
                ->icon('heroicon-o-clipboard-document')
                ->color('warning')
                ->url(\App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'menunggu_kepala_seksi']]]));
        }

        // 4. Operasional flow: Kanit Kepegawaian (stage 3)
        if ($user->hasRole('kanit_kepegawaian')) {
            $menungguStage3 = PengajuanCuti::forApprover($user)
                ->where('status', 'menunggu_kanit_kepegawaian')
                ->where('user_id', '!=', $user->id)
                ->count();
            if ($menungguStage3 > 0) {
                $stats[] = Stat::make('Menunggu Persetujuan (Kanit Kepegawaian)', $menungguStage3)
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->url(\App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'menunggu_kanit_kepegawaian']]]));
            }
        }

        // 5. Operasional flow: Kasubag TU (stage 4 / final)
        if ($user->hasRole('kasubag_tu')) {
            $kasubagTuFinal = PengajuanCuti::forApprover($user)
                ->where('status', 'menunggu_kasubag_tu')
                ->where('user_id', '!=', $user->id)
                ->count();
            if ($kasubagTuFinal > 0) {
                $stats[] = Stat::make('Menunggu Keputusan Final', $kasubagTuFinal)
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->url(\App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'menunggu_kasubag_tu']]]));
            }
        }

        // 6. Sedang cuti (visible to final approvers)
        if ($user->hasRole(['kasubag_tu', 'pejabat_berwenang'])) {
            $sedangCuti = PengajuanCuti::query()
                ->whereHas('blangkoCuti', function ($query) {
                    $query->where('status', 'disetujui');
                })
                ->whereDate('tanggal_mulai', '<=', today())
                ->whereDate('tanggal_selesai', '>=', today())
                ->count();
            $stats[] = Stat::make('Pegawai Sedang Cuti', $sedangCuti)
                ->icon('heroicon-o-users')
                ->color('success');
        }

        return $stats;
    }
}
