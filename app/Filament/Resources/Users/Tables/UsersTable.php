<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\SaldoCuti;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;

        return $table
            ->headerActions([
                \Filament\Actions\Action::make('download_template')
                    ->label('Unduh Template Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('users.template'))
                    ->visible(fn () => auth()->user()->hasRole(['super_admin', 'admin'])),
                \Filament\Actions\Action::make('import')
                    ->label('Import Pegawai')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        \Filament\Forms\Components\FileUpload::make('file')
                            ->label('File Excel')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->storeFiles(false)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $file = is_array($data['file']) ? reset($data['file']) : $data['file'];
                        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\UserImport, $file);
                        \Filament\Notifications\Notification::make()
                            ->title('Berhasil import data pegawai')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->hasRole(['super_admin', 'admin'])),
            ])
            ->columns([
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('unitKerja.nama_unit')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unitKerja.jenis')
                    ->label('Jenis Unit')
                    ->badge()
                    ->color(fn ($state) => $state === 'operasional' ? 'warning' : 'info'),
                IconColumn::make('is_profile_completed')
                    ->label('Profil')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(','),
                TextColumn::make('saldoCuti.saldo_n')
                    ->label('Saldo N')
                    ->suffix(' hari')
                    ->toggleable()
                    ->visible($isSuperAdmin),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->relationship('unitKerja', 'nama_unit'),
                \Filament\Tables\Filters\SelectFilter::make('jenis_unit')
                    ->label('Jenis Unit')
                    ->options(['administrasi' => 'Administrasi', 'operasional' => 'Operasional'])
                    ->query(fn (Builder $q, $data) =>
                        $q->when($data['value'] ?? null, fn ($q, $v) =>
                            $q->whereHas('unitKerja', fn ($q2) => $q2->where('jenis', $v))
                        )
                    ),
                \Filament\Tables\Filters\TernaryFilter::make('is_profile_completed')
                    ->label('Profil Dilengkapi'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                \Filament\Actions\Action::make('reset_saldo')
                    ->label('Reset Saldo')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->action(function ($record) {
                        $saldo = $record->saldoCuti;
                        if ($saldo) {
                            $saldo->update([
                                'saldo_n2' => min($saldo->saldo_n1, 6),
                                'saldo_n1' => min($saldo->saldo_n, 6),
                                'saldo_n' => 12,
                                'saldo_cuti_besar' => 90,
                                'saldo_cuti_sakit' => 365,
                                'saldo_cuti_melahirkan' => 90,
                                'saldo_cuti_alasan_penting' => 30,
                                'tahun_berjalan' => date('Y')
                            ]);
                        } else {
                            SaldoCuti::create([
                                'user_id'                     => $record->id,
                                'saldo_n'                     => 12,
                                'saldo_n1'                    => 0,
                                'saldo_n2'                    => 0,
                                'saldo_cuti_besar'            => 90,
                                'saldo_cuti_sakit'            => 365,
                                'saldo_cuti_melahirkan'       => 90,
                                'saldo_cuti_alasan_penting'   => 30,
                                'tahun_berjalan'              => date('Y'),
                            ]);
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Saldo Berhasil Direset')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
