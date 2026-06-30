<?php

namespace App\Filament\Resources\HariLiburs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HariLiburForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')->required()->unique(ignoreRecord: true),
                TextInput::make('keterangan')->required()->maxLength(255),
                Select::make('jenis')
                    ->options([
                        'libur_nasional' => 'Libur Nasional',
                        'cuti_bersama' => 'Cuti Bersama',
                    ])
                    ->required(),
            ]);
    }
}
