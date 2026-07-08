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
            if ($user->hasRole(['kanit', 'kasubag'])) {
                $query = PengajuanCuti::forApprover($user)
                    ->where('status', 'menunggu_atasan');
                
                $query->where(function($q) use ($user) {
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
            }

            if ($user->hasRole('pejabat_berwenang')) {
                $menungguFinal = PengajuanCuti::forApprover($user)
                    ->where('status', 'menunggu_pejabat')
                    ->where('user_id', '!=', $user->id)
                    ->count();
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
