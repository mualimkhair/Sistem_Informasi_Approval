<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanCuti;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatistikCutiWidget extends BaseWidget
{
    protected ?string $pollingInterval = '10s';
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    protected function getStats(): array
    {
        $internalSelesaiMenungguKabandara = PengajuanCuti::where('status', 'disetujui')
            ->where(function($q) {
                $q->doesntHave('blangkoCuti')
                  ->orWhereHas('blangkoCuti', fn($b) => $b->where('status', 'menunggu'));
            })->count();

        return [
            Stat::make('Total Pegawai', User::role('pegawai')->count())
                ->url(\App\Filament\Resources\Users\UserResource::getUrl('index')),
            Stat::make('Total Pengajuan Cuti', PengajuanCuti::count())
                ->url(\App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource::getUrl('index')),
            Stat::make('Menunggu Persetujuan Kabandara', $internalSelesaiMenungguKabandara)
                ->color('warning')
                ->url(\App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'disetujui']]])),
            Stat::make('Disetujui Kabandara (Final)', PengajuanCuti::whereHas('blangkoCuti', fn($q) => $q->where('status', 'disetujui'))->count())
                ->color('success')
                ->url(\App\Filament\Resources\BlangkoCutis\BlangkoCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'disetujui']]])),
            Stat::make('Ditolak Kabandara', PengajuanCuti::whereHas('blangkoCuti', fn($q) => $q->where('status', 'ditolak'))->count())
                ->color('danger')
                ->url(\App\Filament\Resources\BlangkoCutis\BlangkoCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'ditolak']]])),
            Stat::make('Menunggu Approval Internal', PengajuanCuti::whereIn('status', [
                'menunggu_atasan',
                'menunggu_kepala_unit', 'menunggu_kepala_seksi',
                'menunggu_kanit_kepegawaian', 'menunggu_kasubag_tu',
            ])->count())
                ->color('warning')
                ->url(\App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource::getUrl('index')),
        ];
    }
}
