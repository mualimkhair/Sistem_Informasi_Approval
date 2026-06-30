<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;

        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Data Pegawai')
                    ->schema([
                        TextInput::make('nip')
                            ->label('NIP')
                            ->required()
                            ->maxLength(18)
                            ->unique(ignoreRecord: true)
                            ->helperText('NIP 18 digit, digunakan sebagai username login'),
                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->hiddenOn('create'),
                        TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->maxLength(255),
                        Select::make('unit_kerja_id')
                            ->label('Unit Kerja')
                            ->relationship('unitKerja', 'nama_unit')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('nomor_telp')
                            ->label('Nomor Telepon')
                            ->tel(),
                        \Filament\Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->rows(2),
                        \Filament\Forms\Components\DatePicker::make('tanggal_masuk')
                            ->label('Tanggal Masuk Kerja'),
                        \Filament\Forms\Components\Toggle::make('is_profile_completed')
                            ->label('Profil Sudah Dilengkapi')
                            ->visible($isSuperAdmin),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Saldo Cuti')
                    ->schema([
                        TextInput::make('saldoCuti.saldo_n')
                            ->label('Saldo N (Tahun Berjalan)')
                            ->numeric()
                            ->minValue(0)
                            ->default(12),
                        TextInput::make('saldoCuti.saldo_n1')
                            ->label('Saldo N-1 (Tahun Lalu)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('saldoCuti.saldo_n2')
                            ->label('Saldo N-2 (2 Tahun Lalu)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        TextInput::make('saldoCuti.saldo_cuti_besar')
                            ->label('Saldo Cuti Besar')
                            ->numeric()
                            ->minValue(0)
                            ->default(90),
                        TextInput::make('saldoCuti.saldo_cuti_sakit')
                            ->label('Saldo Cuti Sakit')
                            ->numeric()
                            ->minValue(0)
                            ->default(365),
                        TextInput::make('saldoCuti.saldo_cuti_melahirkan')
                            ->label('Saldo Cuti Melahirkan')
                            ->numeric()
                            ->minValue(0)
                            ->default(90),
                        TextInput::make('saldoCuti.saldo_cuti_alasan_penting')
                            ->label('Saldo Cuti Alasan Penting')
                            ->numeric()
                            ->minValue(0)
                            ->default(30),
                    ])
                    ->columns(3)
                    ->visible($isSuperAdmin)
                    ->collapsible(),

                \Filament\Schemas\Components\Section::make('Role & Hak Akses')
                    ->schema([
                        Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->required(),
                    ])
                    ->visible($isSuperAdmin)
                    ->collapsible(),
            ]);
    }
}
