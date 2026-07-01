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
        if (!$saldo) return [];

        return [
            Stat::make('Sisa Cuti Tahunan', $saldo->saldo_n + $saldo->saldo_n1 + $saldo->saldo_n2 . ' Hari')
                ->description("N: {$saldo->saldo_n} | N-1: {$saldo->saldo_n1} | N-2: {$saldo->saldo_n2}")
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Cuti Besar', $saldo->saldo_cuti_besar . ' / ' . '90' . ' Hari')
                ->icon('heroicon-o-calendar-days'),
            Stat::make('Cuti Sakit', $saldo->saldo_cuti_sakit . ' / ' . '365' . ' Hari')
                ->icon('heroicon-o-heart'),
            Stat::make('Cuti Melahirkan', $saldo->saldo_cuti_melahirkan . ' / ' . '90' . ' Hari')
                ->icon('heroicon-m-user'),
            Stat::make('Cuti Alasan Penting', $saldo->saldo_cuti_alasan_penting . ' / ' . '30' . ' Hari')
                ->icon('heroicon-o-exclamation-circle'),
            // Stat::make('Cuti Diluar Tanggungan Negara', $saldo->saldo_cuti_diluar_tanggungan_negara . ' / ' . '1095' . ' Hari')
            //     ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}
