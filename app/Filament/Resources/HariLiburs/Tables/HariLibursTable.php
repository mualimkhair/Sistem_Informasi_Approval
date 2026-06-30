<?php

namespace App\Filament\Resources\HariLiburs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class HariLibursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')->date()->sortable(),
                TextColumn::make('keterangan')->searchable(),
                TextColumn::make('jenis')->badge()->color(fn (string $state): string => match ($state) {
                    'libur_nasional' => 'danger',
                    'cuti_bersama' => 'warning',
                }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
