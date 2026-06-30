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

        if ($user->hasRole(['kanit', 'kasubag', 'pejabat_berwenang'])) {
            $menungguLevel1 = 0;
            if ($user->hasRole('kanit')) {
                $menungguLevel1 += PengajuanCuti::whereNull('keputusan_kanit')
                    ->where('status', 'menunggu_atasan')
                    ->whereHas('user', fn($q) => $q->where('unit_kerja_id', $user->unit_kerja_id))
                    ->count();
            }
            if ($user->hasRole('kasubag')) {
                $menungguLevel1 += PengajuanCuti::whereNull('keputusan_kasubag')
                    ->where('status', 'menunggu_atasan')
                    ->whereHas('user', fn($q) => $q->where('unit_kerja_id', $user->unit_kerja_id))
                    ->count();
            }

            if ($user->hasRole(['kanit', 'kasubag'])) {
                $stats[] = Stat::make('Menunggu Keputusan Anda', $menungguLevel1)
                    ->icon('heroicon-o-clock')
                    ->color('warning');
            }

            if ($user->hasRole('pejabat_berwenang')) {
                $menungguFinal = PengajuanCuti::where('status', 'menunggu_pejabat')->count();
                $stats[] = Stat::make('Menunggu Keputusan Final', $menungguFinal)
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning');
                    
                $sedangCuti = PengajuanCuti::where('status', 'disetujui')
                    ->where('tanggal_mulai', '<=', now())
                    ->where('tanggal_selesai', '>=', now())
                    ->count();
                $stats[] = Stat::make('Pegawai Sedang Cuti', $sedangCuti)
                    ->icon('heroicon-o-users')
                    ->color('success');
            }
        }



        return $stats;
    }
}
