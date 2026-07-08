<?php

namespace App\Filament\Resources\PersetujuanCutis\Tables;

use App\Models\PengajuanCuti;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;

class PersetujuanCutisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                \Filament\Actions\Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($livewire) {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\PengajuanCutiExport($livewire->getFilteredTableQuery()),
                            'Rekap-Persetujuan-Cuti.xlsx'
                        );
                    })
                    ->visible(fn () => auth()->user()->hasRole(['super_admin', 'admin'])),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('Pegawai')->searchable()->sortable(),
                TextColumn::make('user.unitKerja.nama_unit')->label('Unit Kerja')->searchable()->sortable(),
                TextColumn::make('jenis_cuti')->badge()->sortable()->searchable(),
                TextColumn::make('tanggal_mulai')->date()->sortable(),
                TextColumn::make('tanggal_selesai')->date()->sortable(),
                TextColumn::make('lama_cuti')->label('Lama (Hari)')->sortable(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'menunggu_atasan' => 'Menunggu Atasan',
                        'menunggu_pejabat' => 'Menunggu Pejabat',
                        'disetujui' => 'Disetujui',
                        'ditolak_kanit' => 'Ditolak Kanit',
                        'ditolak_kasubag' => 'Ditolak Kasubag',
                        'ditolak_pejabat' => 'Ditolak Pejabat',
                        'perubahan' => 'Perlu Perubahan',
                        'ditangguhkan' => 'Ditangguhkan',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
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
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_mulai', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_selesai', '<=', $date),
                            );
                    })
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                \Filament\Actions\Action::make('keputusan_kanit')
                    ->label('Keputusan Kanit')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(
                        fn($record) =>
                        auth()->user()->hasRole('kanit')
                        && $record->status === 'menunggu_atasan'
                        && is_null($record->keputusan_kanit)
                        && $record->user_id !== auth()->id()
                    )
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn($record) => new \Illuminate\Support\HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai} s/d {$record->tanggal_selesai} ({$record->lama_cuti} hari)<br>
                                <strong>Alasan:</strong> {$record->alasan_cuti}"
                            ))
                            ->columnSpanFull(),
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
                            ->required(fn($get) => $get('keputusan_kanit') !== 'disetujui')
                            ->visible(fn($get) => !empty($get('keputusan_kanit')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        $user = auth()->user();
                        if ($record->user_id === $user->id) {
                            abort(403, 'Anda tidak dapat menyetujui pengajuan cuti Anda sendiri.');
                        }
                        if ($record->unitKerja?->kepala_unit_id !== $user->id && !$user->hasRole(['super_admin', 'admin'])) {
                            abort(403, 'Anda bukan supervisor Kanit untuk unit pegawai ini.');
                        }

                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_kanit' => $data['keputusan_kanit'],
                                'alasan_kanit' => $data['alasan_kanit'] ?? null,
                            ]);
                        });

                        \Filament\Notifications\Notification::make()->title('Keputusan Kanit berhasil disimpan.')->success()->send();
                    }),

                \Filament\Actions\Action::make('keputusan_kasubag')
                    ->label('Keputusan Kasubag')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(
                        fn($record) =>
                        auth()->user()->hasRole('kasubag')
                        && $record->status === 'menunggu_atasan'
                        && is_null($record->keputusan_kasubag)
                        && $record->user_id !== auth()->id()
                    )
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn($record) => new \Illuminate\Support\HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai} s/d {$record->tanggal_selesai} ({$record->lama_cuti} hari)<br>
                                <strong>Alasan:</strong> {$record->alasan_cuti}<br>
                                <strong>Catatan Kanit:</strong> " . ($record->alasan_kanit ?? '-')
                            ))
                            ->columnSpanFull(),
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
                            ->required(fn($get) => $get('keputusan_kasubag') !== 'disetujui')
                            ->visible(fn($get) => !empty($get('keputusan_kasubag')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        $user = auth()->user();
                        if ($record->user_id === $user->id) {
                            abort(403, 'Anda tidak dapat menyetujui pengajuan cuti Anda sendiri.');
                        }
                        if ($record->seksi?->kepala_seksi_id !== $user->id && !$user->hasRole(['super_admin', 'admin'])) {
                            abort(403, 'Anda bukan supervisor Kasubag untuk unit pegawai ini.');
                        }

                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_kasubag' => $data['keputusan_kasubag'],
                                'alasan_kasubag' => $data['alasan_kasubag'] ?? null,
                            ]);
                        });

                        \Filament\Notifications\Notification::make()->title('Keputusan Kasubag berhasil disimpan.')->success()->send();
                    }),

                \Filament\Actions\Action::make('keputusan_pejabat')
                    ->label('Keputusan Final')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(
                        fn($record) =>
                        auth()->user()->hasRole('pejabat_berwenang')
                        && $record->status === 'menunggu_pejabat'
                        && $record->user_id !== auth()->id()
                    )
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn($record) => new \Illuminate\Support\HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai} s/d {$record->tanggal_selesai} ({$record->lama_cuti} hari)<br>
                                <strong>Alasan:</strong> {$record->alasan_cuti}<br>
                                <strong>Catatan Kanit:</strong> " . ($record->alasan_kanit ?? '-') . "<br>
                                <strong>Catatan Kasubag:</strong> " . ($record->alasan_kasubag ?? '-')
                            ))
                            ->columnSpanFull(),
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
                            ->required(fn($get) => $get('keputusan_pejabat') !== 'disetujui')
                            ->visible(fn($get) => !empty($get('keputusan_pejabat')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_pejabat' => $data['keputusan_pejabat'],
                                'alasan_pejabat' => $data['alasan_pejabat'] ?? null,
                            ]);
                        });
                        \Filament\Notifications\Notification::make()->title('Keputusan final berhasil disimpan.')->success()->send();
                    }),

                \Filament\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn($record) => route('pengajuan-cuti.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->status === 'disetujui' || auth()->user()->hasRole(['super_admin', 'admin'])),

                \Filament\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'admin']))
            ])
            ->defaultSort('created_at', 'desc');
    }
}
