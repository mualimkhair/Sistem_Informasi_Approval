<?php

namespace App\Filament\Resources\Seksis\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SeksiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_seksi')
                    ->required(),
                \Filament\Forms\Components\Select::make('kepala_seksi_id')
                    ->label('Kepala Seksi / Kasubag')
                    ->relationship('kepalaSeksi', 'nama')
                    ->searchable()
                    ->preload()
                    ->nullable(),
            ]);
    }
}
