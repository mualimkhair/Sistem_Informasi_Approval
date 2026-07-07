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
        $tersediaTahunan = \App\Services\CutiService::hitungSaldoTersedia($user, 'tahunan');
        $tersediaBesar = \App\Services\CutiService::hitungSaldoTersedia($user, 'besar');
        $tersediaSakit = \App\Services\CutiService::hitungSaldoTersedia($user, 'sakit');
        $tersediaMelahirkan = \App\Services\CutiService::hitungSaldoTersedia($user, 'melahirkan');
        $tersediaAlasanPenting = \App\Services\CutiService::hitungSaldoTersedia($user, 'alasan_penting');
        if (!$saldo) return [];

        return [
            Stat::make('Sisa Cuti Tahunan', $tersediaTahunan . ' Hari')
                ->description("N: {$saldo->saldo_n} | N-1: {$saldo->saldo_n1} | N-2: {$saldo->saldo_n2}")
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Cuti Besar', $tersediaBesar . ' / ' . '90' . ' Hari')
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Cuti Sakit', $tersediaSakit . ' / ' . '365' . ' Hari')
                ->icon('heroicon-o-heart'),
            Stat::make('Cuti Melahirkan', $tersediaMelahirkan . ' / ' . '90' . ' Hari')
                ->icon('heroicon-m-user'),
            Stat::make('Cuti Alasan Penting', $tersediaAlasanPenting . ' / ' . '30' . ' Hari')
                ->icon('heroicon-o-exclamation-circle'),
            // Stat::make('Cuti Diluar Tanggungan Negara', $saldo->saldo_cuti_diluar_tanggungan_negara . ' / ' . '1095' . ' Hari')
            //     ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}
