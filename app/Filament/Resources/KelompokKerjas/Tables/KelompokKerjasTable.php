<?php

namespace App\Filament\Resources\KelompokKerjas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class KelompokKerjasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('unitKerja.nama_unit')->label('Unit Kerja')->sortable(),
                TextColumn::make('nama_kelompok')->searchable(),
                TextColumn::make('hari_libur_1'),
                TextColumn::make('hari_libur_2'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
