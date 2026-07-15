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
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->hiddenOn('create'),
                        TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->maxLength(255),
                        Select::make('pangkat_gol')
                            ->label('Pangkat/Golongan')
                            ->options(\App\Models\User::PANGKAT_GOLONGAN)
                            ->searchable()
                            ->nullable()
                            ->rule(\Illuminate\Validation\Rule::in(array_keys(\App\Models\User::PANGKAT_GOLONGAN))),
                        Select::make('seksi_id')
                            ->label('Seksi (Jabatan Kasi/Kasubag)')
                            ->relationship('seksi', 'nama_seksi')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Dikelola otomatis lewat menu Manajemen Seksi — tidak dapat diubah di sini.'),
                        Select::make('unit_kerja_id')
                            ->label('Unit Kerja')
                            ->relationship('unitKerja', 'nama_unit')
                            ->searchable()
                            ->preload()
                            ->nullable(),
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

                \Filament\Schemas\Components\Group::make()
                    ->relationship('saldoCuti')
                    ->schema([
                        \Filament\Schemas\Components\Section::make('Saldo Cuti')
                            ->schema([
                                \Filament\Forms\Components\Hidden::make('tahun_berjalan')
                                    ->default(date('Y')),
                                TextInput::make('saldo_n')
                                    ->label('Saldo N (Tahun Berjalan)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(12),
                                TextInput::make('saldo_n1')
                                    ->label('Saldo N-1 (Tahun Lalu)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                                TextInput::make('saldo_n2')
                                    ->label('Saldo N-2 (2 Tahun Lalu)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0),
                                TextInput::make('saldo_cuti_besar')
                                    ->label('Saldo Cuti Besar')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(90),
                                TextInput::make('saldo_cuti_sakit')
                                    ->label('Saldo Cuti Sakit')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(365),
                                TextInput::make('saldo_cuti_melahirkan')
                                    ->label('Saldo Cuti Melahirkan')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(90),
                                TextInput::make('saldo_cuti_alasan_penting')
                                    ->label('Saldo Cuti Alasan Penting')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(30),
                            ])
                            ->columns(3)
                            ->collapsible()
                    ])
                    ->visible($isSuperAdmin),

                \Filament\Schemas\Components\Section::make('Role & Hak Akses')
                    ->schema([
                        Select::make('roles')
                            ->label('Role')
                            ->relationship('roles', 'name', fn($query) => $query
                                ->where('name', '!=', 'super_admin')
                                ->whereNotIn('name', ['kasubag', 'kanit']) // Dikelola oleh Observer Seksi/Unit
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->default(fn() => [\Spatie\Permission\Models\Role::where('name', 'pegawai')->value('id')])
                            ->live()
                            ->helperText('Role kasubag/kanit dikelola otomatis oleh sistem saat assign di Seksi/Unit Kerja.')
                            ->required(),
                    ])
                    ->visible($isSuperAdmin)
                    ->collapsible(),
            ]);
    }
}
