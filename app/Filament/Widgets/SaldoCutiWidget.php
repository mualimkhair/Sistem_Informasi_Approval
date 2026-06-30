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
            Stat::make('Cuti Besar / Sakit', $saldo->saldo_cuti_besar . ' / ' . $saldo->saldo_cuti_sakit . ' Hari')
                ->icon('heroicon-o-briefcase'),
            Stat::make('Melahirkan / Alasan Penting', $saldo->saldo_cuti_melahirkan . ' / ' . $saldo->saldo_cuti_alasan_penting . ' Hari')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }
}
