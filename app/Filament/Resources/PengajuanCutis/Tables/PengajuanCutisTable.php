<?php

namespace App\Filament\Resources\PengajuanCutis\Tables;

use App\Models\PengajuanCuti;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PengajuanCutisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                \Filament\Tables\Actions\Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($livewire) {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\PengajuanCutiExport($livewire->getFilteredTableQuery()),
                            'Rekap-Pengajuan-Cuti.xlsx'
                        );
                    })
                    ->visible(fn () => auth()->user()->hasRole(['super_admin', 'admin'])),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('Pegawai')->searchable(),
                TextColumn::make('user.unitKerja.nama_unit')->label('Unit Kerja')->sortable(),
                TextColumn::make('jenis_cuti')->badge(),
                TextColumn::make('tanggal_mulai')->date(),
                TextColumn::make('tanggal_selesai')->date(),
                TextColumn::make('lama_cuti')->label('Lama (Hari)'),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'menunggu_atasan', 'menunggu_pejabat' => 'warning',
                    'disetujui' => 'success',
                    'ditolak_kanit', 'ditolak_kasubag', 'ditolak_pejabat' => 'danger',
                    'ditangguhkan', 'perubahan' => 'gray',
                    default => 'gray',
                }),
            ])
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('dari')->label('Dari Tanggal'),
                        DatePicker::make('sampai')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_mulai', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_selesai', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Action::make('keputusan_kanit')
                    ->label('Keputusan Kanit')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(fn ($record) => 
                        auth()->user()->hasRole('kanit') 
                        && $record->status === 'menunggu_atasan' 
                        && is_null($record->keputusan_kanit)
                        && $record->user_id !== auth()->id()
                    )
                    ->form([
                        Select::make('keputusan_kanit')
                            ->label('Keputusan')
                            ->options([
                                'disetujui' => 'Disetujui',
                                'tidak_disetujui' => 'Tidak Disetujui',
                                'perubahan' => 'Perlu Perubahan',
                                'ditangguhkan' => 'Ditangguhkan',
                            ])
                            ->required()
                            ->live(),
                        Textarea::make('alasan_kanit')
                            ->label('Alasan / Catatan')
                            ->required(fn ($get) => $get('keputusan_kanit') !== 'disetujui')
                            ->visible(fn ($get) => !empty($get('keputusan_kanit')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        $record->update([
                            'keputusan_kanit' => $data['keputusan_kanit'],
                            'alasan_kanit' => $data['alasan_kanit'] ?? null,
                        ]);
                        \Filament\Notifications\Notification::make()->title('Keputusan Kanit berhasil disimpan.')->success()->send();
                    }),

                Action::make('keputusan_kasubag')
                    ->label('Keputusan Kasubag')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(fn ($record) => 
                        auth()->user()->hasRole('kasubag') 
                        && $record->status === 'menunggu_atasan' 
                        && is_null($record->keputusan_kasubag)
                        && $record->user_id !== auth()->id()
                    )
                    ->form([
                        Select::make('keputusan_kasubag')
                            ->label('Keputusan')
                            ->options([
                                'disetujui' => 'Disetujui',
                                'tidak_disetujui' => 'Tidak Disetujui',
                                'perubahan' => 'Perlu Perubahan',
                                'ditangguhkan' => 'Ditangguhkan',
                            ])
                            ->required()
                            ->live(),
                        Textarea::make('alasan_kasubag')
                            ->label('Alasan / Catatan')
                            ->required(fn ($get) => $get('keputusan_kasubag') !== 'disetujui')
                            ->visible(fn ($get) => !empty($get('keputusan_kasubag')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        $record->update([
                            'keputusan_kasubag' => $data['keputusan_kasubag'],
                            'alasan_kasubag' => $data['alasan_kasubag'] ?? null,
                        ]);
                        \Filament\Notifications\Notification::make()->title('Keputusan Kasubag berhasil disimpan.')->success()->send();
                    }),

                Action::make('keputusan_pejabat')
                    ->label('Keputusan Final')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => 
                        auth()->user()->hasRole('pejabat_berwenang') 
                        && $record->status === 'menunggu_pejabat'
                        && $record->user_id !== auth()->id()
                    )
                    ->form([
                        Select::make('keputusan_pejabat')
                            ->label('Keputusan Final')
                            ->options([
                                'disetujui' => 'Disetujui',
                                'tidak_disetujui' => 'Tidak Disetujui',
                                'perubahan' => 'Perlu Perubahan',
                                'ditangguhkan' => 'Ditangguhkan',
                            ])
                            ->required()
                            ->live(),
                        Textarea::make('alasan_pejabat')
                            ->label('Alasan / Catatan')
                            ->required(fn ($get) => $get('keputusan_pejabat') !== 'disetujui')
                            ->visible(fn ($get) => !empty($get('keputusan_pejabat')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        $record->update([
                            'keputusan_pejabat' => $data['keputusan_pejabat'],
                            'alasan_pejabat' => $data['alasan_pejabat'] ?? null,
                        ]);
                        \Filament\Notifications\Notification::make()->title('Keputusan final berhasil disimpan.')->success()->send();
                    }),

                EditAction::make()
                    ->visible(function ($record) {
                        $user = auth()->user();
                        if ($user->hasRole(['super_admin', 'admin'])) return true;
                        if ($record->user_id === $user->id) return $record->status === 'perubahan';
                        return false;
                    }),
                DeleteAction::make()
                    ->visible(function ($record) {
                        $user = auth()->user();
                        if ($user->hasRole(['super_admin', 'admin'])) return true;
                        if ($record->user_id === $user->id) return $record->status !== 'disetujui';
                        return false;
                    }),
                Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn ($record) => route('pengajuan-cuti.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->status === 'disetujui' || auth()->user()->hasRole(['super_admin', 'admin'])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
