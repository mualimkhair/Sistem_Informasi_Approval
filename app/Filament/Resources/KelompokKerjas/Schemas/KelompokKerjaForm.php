<?php

namespace App\Filament\Resources\KelompokKerjas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KelompokKerjaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('unit_kerja_id')
                    ->relationship('unitKerja', 'nama_unit', fn ($query) => $query->where('jenis', 'operasional'))
                    ->required()
                    ->searchable()
                    ->label('Unit Kerja (Operasional)'),
                TextInput::make('nama_kelompok')
                    ->required()
                    ->maxLength(255),
                Select::make('hari_libur_1')
                    ->options([
                        'Senin' => 'Senin', 'Selasa' => 'Selasa', 'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis', 'Jumat' => 'Jumat', 'Sabtu' => 'Sabtu', 'Minggu' => 'Minggu',
                    ])
                    ->searchable()
                    ->required(),
                Select::make('hari_libur_2')
                    ->options([
                        'Senin' => 'Senin', 'Selasa' => 'Selasa', 'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis', 'Jumat' => 'Jumat', 'Sabtu' => 'Sabtu', 'Minggu' => 'Minggu',
                    ])
                    ->searchable()
                    ->required(),
            ]);
    }
}
