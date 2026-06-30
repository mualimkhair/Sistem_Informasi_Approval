<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanCuti;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatistikCutiWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pegawai', User::count()),
            Stat::make('Total Pengajuan Cuti', PengajuanCuti::count()),
            Stat::make('Pengajuan Disetujui', PengajuanCuti::where('status', 'disetujui')->count())
                ->color('success'),
            Stat::make('Pengajuan Menunggu', PengajuanCuti::whereIn('status', ['menunggu_atasan', 'menunggu_pejabat'])->count())
                ->color('warning'),
        ];
    }
}
