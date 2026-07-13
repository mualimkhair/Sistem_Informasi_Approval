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
use App\Services\CutiService;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;

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
                    ->url(fn() => route('users.template'))
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'admin'])),
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
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'admin'])),
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
                TextColumn::make('seksi.nama_seksi')
                    ->label('Seksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unitKerja.nama_unit')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unitKerja.jenis')
                    ->label('Jenis Unit')
                    ->badge()
                    ->color(fn($state) => $state === 'operasional' ? 'warning' : 'info'),
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
                \Filament\Tables\Filters\SelectFilter::make('seksi_id')
                    ->label('Seksi')
                    ->relationship('seksi', 'nama_seksi'),
                \Filament\Tables\Filters\SelectFilter::make('unit_kerja_id')
                    ->label('Unit Kerja')
                    ->relationship('unitKerja', 'nama_unit'),
                \Filament\Tables\Filters\SelectFilter::make('jenis_unit')
                    ->label('Jenis Unit')
                    ->options(['administrasi' => 'Administrasi', 'operasional' => 'Operasional'])
                    ->query(
                        fn(Builder $query, array $data) =>
                        $query->when(
                            $data['value'] ?? null,
                            fn($q, $v) =>
                            $q->whereHas('unitKerja', fn($q2) => $q2->where('jenis', $v))
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
                    ->visible(fn() => auth()->user()->hasRole('super_admin'))
                    ->action(function (User $record) {
                        if ($record->saldoCuti) {
                            CutiService::resetSaldoToDefault($record->saldoCuti);
                        } else {
                            $saldo = new SaldoCuti(['user_id' => $record->id]);
                            CutiService::resetSaldoToDefault($saldo);
                        }
                        Notification::make()->title('Saldo cuti berhasil di-reset ke default.')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
                \Filament\Actions\Action::make('reset_all_saldo')
                    ->label('Reset All Saldo (Rollover)')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Jalankan Rollover Saldo Tahunan?')
                    ->modalDescription(function () {
                        $sudahDijalankan = SaldoCuti::where('last_rollover_year', now()->year)->exists();
                        if ($sudahDijalankan) {
                            return 'PERHATIAN: Rollover sudah pernah dijalankan untuk tahun ini. Jalankan lagi akan melewatkan user yang sudah dirollover.';
                        }
                        return 'Ini akan menjalankan rollover saldo tahunan untuk SELURUH pegawai. N2 akan hangus, N1 → N2, N → N1, N baru = 12.';
                    })
                    ->visible(fn() => auth()->user()->hasRole('super_admin'))
                    ->action(function () {
                        $count = 0;
                        DB::transaction(function () use (&$count) {
                            $saldos = SaldoCuti::all();
                            foreach ($saldos as $saldo) {
                                if ($saldo->user && !$saldo->user->hasRole('super_admin')) {
                                    CutiService::rolloverSaldoTahunan($saldo);
                                    $count++;
                                }
                            }
                        });
                        Notification::make()
                            ->title("Rollover selesai untuk {$count} pegawai.")
                            ->success()
                            ->send();
                    }),


            ]);
    }
}
