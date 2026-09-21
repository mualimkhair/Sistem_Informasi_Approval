<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanCuti;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RiwayatPengajuanWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PengajuanCuti::where('user_id', auth()->id())->latest()->limit(5)
            )
            ->recordUrl(
                fn (PengajuanCuti $record): string => \App\Filament\Resources\PengajuanCutis\PengajuanCutiResource::getUrl('index')
            )
            ->columns([
                TextColumn::make('jenis_cuti')->label('Jenis')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_mulai')->label('Mulai')->date(),
                TextColumn::make('tanggal_selesai')->label('Selesai')->date(),
                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->final_business_status)
                    ->color(fn ($record): string => $record->final_business_status_color),
            ]);
    }
}
