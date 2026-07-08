<?php

namespace App\Filament\Resources\KelompokKerjas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;

class KelompokKerjasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unitKerja.nama_unit')->label('Unit Kerja')->searchable()->sortable(),
                TextColumn::make('nama_kelompok')->searchable()->sortable(),
                TextColumn::make('hari_libur_1')->sortable(),
                TextColumn::make('hari_libur_2')->sortable(),
            ])
            ->filters([
                SelectFilter::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->relationship('unitKerja', 'nama_unit'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
