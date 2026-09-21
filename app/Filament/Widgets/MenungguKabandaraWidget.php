<?php

namespace App\Filament\Widgets;

use App\Models\BlangkoCuti;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MenungguKabandaraWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('pejabat_berwenang');
    }

    protected function getStats(): array
    {
        $menunggu = BlangkoCuti::where('status', 'menunggu')->count();

        if ($menunggu === 0) {
            return [];
        }

        return [
            Stat::make('Blangko Menunggu Persetujuan', $menunggu)
                ->icon('heroicon-o-document-check')
                ->color('warning')
                ->url(\App\Filament\Resources\BlangkoCutis\BlangkoCutiResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'menunggu']]])),
        ];
    }
}
