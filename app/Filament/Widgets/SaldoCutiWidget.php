<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SaldoCutiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $saldo = Auth::user()->saldoCuti;
        $user = Auth::user();
        
        if (!$saldo) return [];

        $tersediaTahunan = \App\Services\CutiService::hitungSaldoTersedia($user, 'tahunan');
        $tersediaBesar = \App\Services\CutiService::hitungSaldoTersedia($user, 'besar');
        $tersediaSakit = \App\Services\CutiService::hitungSaldoTersedia($user, 'sakit');
        $tersediaMelahirkan = \App\Services\CutiService::hitungSaldoTersedia($user, 'melahirkan');
        $tersediaAlasanPenting = \App\Services\CutiService::hitungSaldoTersedia($user, 'alasan_penting');

        // Simulasi bucket tahunan yang sudah dikurangi hold
        $n2 = $saldo->saldo_n2;
        $n1 = $saldo->saldo_n1;
        $n = $saldo->saldo_n;

        $activeHoldsTahunan = \App\Services\CutiService::getActiveHoldsByJenis($user, 'tahunan');
        if ($activeHoldsTahunan > 0) {
            if ($n2 >= $activeHoldsTahunan) {
                $n2 -= $activeHoldsTahunan;
                $activeHoldsTahunan = 0;
            } else {
                $activeHoldsTahunan -= $n2;
                $n2 = 0;
            }
            if ($activeHoldsTahunan > 0) {
                if ($n1 >= $activeHoldsTahunan) {
                    $n1 -= $activeHoldsTahunan;
                    $activeHoldsTahunan = 0;
                } else {
                    $activeHoldsTahunan -= $n1;
                    $n1 = 0;
                }
            }
            if ($activeHoldsTahunan > 0) {
                $n = max(0, $n - $activeHoldsTahunan);
            }
        }

        return [
            Stat::make('Sisa Cuti Tahunan', $tersediaTahunan . ' Hari')
                ->description("N: {$n} | N-1: {$n1} | N-2: {$n2}")
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Cuti Besar', $tersediaBesar . ' / ' . '90' . ' Hari')
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Cuti Sakit', $tersediaSakit . ' / ' . '365' . ' Hari')
                ->icon('heroicon-o-heart'),
            Stat::make('Cuti Melahirkan', $tersediaMelahirkan . ' / ' . '90' . ' Hari')
                ->icon('heroicon-m-user'),
            Stat::make('Cuti Alasan Penting', $tersediaAlasanPenting . ' / ' . '30' . ' Hari')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}
