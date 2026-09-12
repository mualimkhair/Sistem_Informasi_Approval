<?php

namespace App\Filament\Resources\PersetujuanCutis\Tables;

use App\Exports\PengajuanCutiExport;
use App\Models\PengajuanCuti;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;

class PersetujuanCutisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($livewire) {
                        return Excel::download(
                            new PengajuanCutiExport($livewire->getFilteredTableQuery()),
                            'Rekap-Persetujuan-Cuti.xlsx'
                        );
                    })
                    ->visible(fn () => auth()->user()->hasRole(['super_admin', 'admin'])),
            ])
            ->columns([
                TextColumn::make('user.nama')->label('Pegawai')->searchable()->sortable(),
                TextColumn::make('user.unitKerja.nama_unit')->label('Unit Kerja')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('jenis_cuti')->label('Jenis Cuti')->badge()->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_mulai')->label('Tgl Mulai')->date()->sortable(),
                TextColumn::make('tanggal_selesai')->label('Tgl Selesai')->date()->sortable(),
                TextColumn::make('lama_cuti')->label('Lama (Hari)')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'menunggu_atasan' => 'Menunggu Atasan',
                        'menunggu_pejabat' => 'Menunggu Pejabat',
                        'disetujui' => 'Disetujui',
                        'ditolak_kanit' => 'Ditolak Kanit',
                        'ditolak_kasubag' => 'Ditolak Kasubag',
                        'ditolak_pejabat' => 'Ditolak Pejabat',
                        'menunggu_kepala_unit' => 'Menunggu Kepala Unit',
                        'menunggu_kepala_seksi' => 'Menunggu Kepala Seksi',
                        'menunggu_kanit_kepegawaian' => 'Menunggu Kanit Kepegawaian',
                        'menunggu_kasubag_tu' => 'Menunggu Kasubag TU',
                        'ditolak_kepala_unit' => 'Ditolak Kepala Unit',
                        'ditolak_kepala_seksi' => 'Ditolak Kepala Seksi',
                        'ditolak_kanit_kepegawaian' => 'Ditolak Kanit Kepegawaian',
                        'ditolak_kasubag_tu' => 'Ditolak Kasubag TU',
                        'perubahan' => 'Perlu Perubahan',
                        'ditangguhkan' => 'Ditangguhkan',
                        default => ucwords(str_replace('_', ' ', $state)),
                    })
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu_atasan', 'menunggu_pejabat',
                        'menunggu_kepala_unit', 'menunggu_kepala_seksi',
                        'menunggu_kanit_kepegawaian', 'menunggu_kasubag_tu' => 'warning',
                        'disetujui' => 'success',
                        'ditolak_kanit', 'ditolak_kasubag', 'ditolak_pejabat',
                        'ditolak_kepala_unit', 'ditolak_kepala_seksi',
                        'ditolak_kanit_kepegawaian', 'ditolak_kasubag_tu' => 'danger',
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
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                Action::make('keputusan_kanit')
                    ->label('Keputusan Kanit')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(
                        fn ($record) => $record->tipe_aliran === 'administrasi'
                        && auth()->user()->hasRole('kanit')
                        && $record->status === 'menunggu_atasan'
                        && is_null($record->keputusan_kanit)
                        && $record->user_id != auth()->id()
                    )
                    ->form([
                        Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn ($record) => new HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai->format('d-m-Y')} s/d {$record->tanggal_selesai->format('d-m-Y')} ({$record->lama_cuti} hari)<br>
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
                            ->required(fn ($get) => $get('keputusan_kanit') !== 'disetujui')
                            ->visible(fn ($get) => ! empty($get('keputusan_kanit')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        $user = auth()->user();
                        if ($record->user_id == $user->id) {
                            abort(403, 'Anda tidak dapat menyetujui pengajuan cuti Anda sendiri.');
                        }
                        if ($record->unitKerja?->kepala_unit_id != $user->id && ! $user->hasRole(['super_admin', 'admin'])) {
                            abort(403, 'Anda bukan supervisor Kanit untuk unit pegawai ini.');
                        }

                        DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_kanit' => $data['keputusan_kanit'],
                                'alasan_kanit' => $data['alasan_kanit'] ?? null,
                            ]);
                        });

                        Notification::make()->title('Keputusan Kanit berhasil disimpan.')->success()->send();
                    }),

                Action::make('keputusan_kasubag')
                    ->label('Keputusan Kasubag')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(
                        fn ($record) => $record->tipe_aliran === 'administrasi'
                        && auth()->user()->hasRole('kasubag')
                        && $record->status === 'menunggu_atasan'
                        && is_null($record->keputusan_kasubag)
                        && $record->user_id != auth()->id()
                    )
                    ->form([
                        Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn ($record) => new HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai->format('d-m-Y')} s/d {$record->tanggal_selesai->format('d-m-Y')} ({$record->lama_cuti} hari)<br>
                                <strong>Alasan:</strong> {$record->alasan_cuti}<br>
                                <strong>Catatan Kanit:</strong> ".($record->alasan_kanit ?? '-')
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
                            ->required(fn ($get) => $get('keputusan_kasubag') !== 'disetujui')
                            ->visible(fn ($get) => ! empty($get('keputusan_kasubag')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        $user = auth()->user();
                        if ($record->user_id == $user->id) {
                            abort(403, 'Anda tidak dapat menyetujui pengajuan cuti Anda sendiri.');
                        }
                        if ($record->seksi?->kepala_seksi_id != $user->id && ! $user->hasRole(['super_admin', 'admin'])) {
                            abort(403, 'Anda bukan supervisor Kasubag untuk unit pegawai ini.');
                        }

                        DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_kasubag' => $data['keputusan_kasubag'],
                                'alasan_kasubag' => $data['alasan_kasubag'] ?? null,
                            ]);
                        });

                        Notification::make()->title('Keputusan Kasubag berhasil disimpan.')->success()->send();
                    }),



                Action::make('keputusan_kepala_unit')
                    ->label('Keputusan Kepala Unit')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(
                        fn ($record) => $record->tipe_aliran === 'operasional'
                        && auth()->user()->hasRole('kanit')
                        && $record->status === 'menunggu_kepala_unit'
                        && is_null($record->keputusan_kepala_unit)
                        && $record->kepala_unit_id == auth()->id()
                        && $record->user_id != auth()->id()
                    )
                    ->form([
                        Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn ($record) => new HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai->format('d-m-Y')} s/d {$record->tanggal_selesai->format('d-m-Y')} ({$record->lama_cuti} hari)<br>
                                <strong>Alasan:</strong> {$record->alasan_cuti}"
                            ))
                            ->columnSpanFull(),
                        Select::make('keputusan_kepala_unit')
                            ->label('Keputusan')
                            ->options([
                                'disetujui' => 'Disetujui',
                                'tidak_disetujui' => 'Tidak Disetujui',
                                'perubahan' => 'Perlu Perubahan',
                                'ditangguhkan' => 'Ditangguhkan',
                            ])
                            ->required()
                            ->live(),
                        Textarea::make('alasan_kepala_unit')
                            ->label('Alasan / Catatan')
                            ->required(fn ($get) => $get('keputusan_kepala_unit') !== 'disetujui')
                            ->visible(fn ($get) => ! empty($get('keputusan_kepala_unit')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_kepala_unit' => $data['keputusan_kepala_unit'],
                                'alasan_kepala_unit' => $data['alasan_kepala_unit'] ?? null,
                            ]);
                        });
                        Notification::make()->title('Keputusan Kepala Unit berhasil disimpan.')->success()->send();
                    }),

                Action::make('keputusan_kepala_seksi')
                    ->label('Keputusan Kepala Seksi')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(
                        fn ($record) => $record->tipe_aliran === 'operasional'
                        && auth()->user()->hasRole('kasubag')
                        && $record->status === 'menunggu_kepala_seksi'
                        && is_null($record->keputusan_kepala_seksi)
                        && $record->kepala_seksi_id == auth()->id()
                        && $record->user_id != auth()->id()
                    )
                    ->form([
                        Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn ($record) => new HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai->format('d-m-Y')} s/d {$record->tanggal_selesai->format('d-m-Y')} ({$record->lama_cuti} hari)<br>
                                <strong>Alasan:</strong> {$record->alasan_cuti}"
                            ))
                            ->columnSpanFull(),
                        Select::make('keputusan_kepala_seksi')
                            ->label('Keputusan')
                            ->options([
                                'disetujui' => 'Disetujui',
                                'tidak_disetujui' => 'Tidak Disetujui',
                                'perubahan' => 'Perlu Perubahan',
                                'ditangguhkan' => 'Ditangguhkan',
                            ])
                            ->required()
                            ->live(),
                        Textarea::make('alasan_kepala_seksi')
                            ->label('Alasan / Catatan')
                            ->required(fn ($get) => $get('keputusan_kepala_seksi') !== 'disetujui')
                            ->visible(fn ($get) => ! empty($get('keputusan_kepala_seksi')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_kepala_seksi' => $data['keputusan_kepala_seksi'],
                                'alasan_kepala_seksi' => $data['alasan_kepala_seksi'] ?? null,
                            ]);
                        });
                        Notification::make()->title('Keputusan Kepala Seksi berhasil disimpan.')->success()->send();
                    }),

                Action::make('keputusan_kanit_kepegawaian')
                    ->label('Keputusan Kanit Kepegawaian')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(
                        fn ($record) => $record->tipe_aliran === 'operasional'
                        && auth()->user()->hasRole('kanit_kepegawaian')
                        && $record->status === 'menunggu_kanit_kepegawaian'
                        && is_null($record->keputusan_kanit_kepegawaian)
                        && $record->kanit_kepegawaian_id == auth()->id()
                        && $record->user_id != auth()->id()
                    )
                    ->form([
                        Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn ($record) => new HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai->format('d-m-Y')} s/d {$record->tanggal_selesai->format('d-m-Y')} ({$record->lama_cuti} hari)<br>
                                <strong>Alasan:</strong> {$record->alasan_cuti}"
                            ))
                            ->columnSpanFull(),
                        Select::make('keputusan_kanit_kepegawaian')
                            ->label('Keputusan')
                            ->options([
                                'disetujui' => 'Disetujui',
                                'tidak_disetujui' => 'Tidak Disetujui',
                                'perubahan' => 'Perlu Perubahan',
                                'ditangguhkan' => 'Ditangguhkan',
                            ])
                            ->required()
                            ->live(),
                        Textarea::make('alasan_kanit_kepegawaian')
                            ->label('Alasan / Catatan')
                            ->required(fn ($get) => $get('keputusan_kanit_kepegawaian') !== 'disetujui')
                            ->visible(fn ($get) => ! empty($get('keputusan_kanit_kepegawaian')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_kanit_kepegawaian' => $data['keputusan_kanit_kepegawaian'],
                                'alasan_kanit_kepegawaian' => $data['alasan_kanit_kepegawaian'] ?? null,
                            ]);
                        });
                        Notification::make()->title('Keputusan Kanit Kepegawaian berhasil disimpan.')->success()->send();
                    }),

                Action::make('keputusan_kasubag_tu')
                    ->label('Keputusan Kasubag TU')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(
                        fn ($record) => $record->tipe_aliran === 'operasional'
                        && auth()->user()->hasRole('kasubag_tu')
                        && $record->status === 'menunggu_kasubag_tu'
                        && is_null($record->keputusan_kasubag_tu)
                        && $record->kasubag_tu_id == auth()->id()
                        && $record->user_id != auth()->id()
                    )
                    ->form([
                        Placeholder::make('detail_pengajuan')
                            ->label('Detail Pengajuan')
                            ->content(fn ($record) => new HtmlString(
                                "<strong>Pegawai:</strong> {$record->user->nama}<br>
                                <strong>Jenis Cuti:</strong> {$record->jenis_cuti}<br>
                                <strong>Tanggal:</strong> {$record->tanggal_mulai->format('d-m-Y')} s/d {$record->tanggal_selesai->format('d-m-Y')} ({$record->lama_cuti} hari)<br>
                                <strong>Alasan:</strong> {$record->alasan_cuti}"
                            ))
                            ->columnSpanFull(),
                        Select::make('keputusan_kasubag_tu')
                            ->label('Keputusan Final')
                            ->options([
                                'disetujui' => 'Disetujui',
                                'tidak_disetujui' => 'Tidak Disetujui',
                                'perubahan' => 'Perlu Perubahan',
                                'ditangguhkan' => 'Ditangguhkan',
                            ])
                            ->required()
                            ->live(),
                        Textarea::make('alasan_kasubag_tu')
                            ->label('Alasan / Catatan')
                            ->required(fn ($get) => $get('keputusan_kasubag_tu') !== 'disetujui')
                            ->visible(fn ($get) => ! empty($get('keputusan_kasubag_tu')))
                            ->rows(3),
                    ])
                    ->action(function (PengajuanCuti $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $pengajuan = PengajuanCuti::lockForUpdate()->findOrFail($record->id);
                            $pengajuan->update([
                                'keputusan_kasubag_tu' => $data['keputusan_kasubag_tu'],
                                'alasan_kasubag_tu' => $data['alasan_kasubag_tu'] ?? null,
                            ]);
                        });
                        Notification::make()->title('Keputusan Kasubag TU berhasil disimpan.')->success()->send();
                    }),

                Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn ($record) => ($record->blangkoCuti && in_array($record->blangkoCuti->status, ['disetujui', 'ditolak'])) || auth()->user()->hasRole(['super_admin', 'admin']))
                    ->modalHeading('DOKUMEN CUTI')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Tutup'))
                    ->modalContent(function ($record) {
                        $blangko = $record->blangkoCuti;
                        
                        // Auto-recovery for missing Blangko Cuti PDF (e.g. historical records)
                        if ($blangko && (!$blangko->file_blangko_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_blangko_path))) {
                            \App\Services\CutiService::generateAndSaveFinalDocuments($blangko);
                            $blangko->refresh();
                        }
                        
                        $suratIzinName = match($record->jenis_cuti) {
                            'cuti_tahunan' => 'Surat Izin Cuti Tahunan',
                            'cuti_besar' => 'Surat Izin Cuti Besar',
                            'cuti_sakit' => 'Surat Izin Cuti Sakit',
                            'cuti_melahirkan' => 'Surat Izin Cuti Bersalin',
                            'cuti_alasan_penting' => 'Surat Izin Cuti Alasan Penting',
                            'cuti_diluar_tanggungan_negara' => 'Surat Izin Cuti di Luar Tanggungan Negara',
                            default => 'Surat Izin Cuti'
                        };

                        $html = '<div class="space-y-4">';
                        
                        if ($blangko && $blangko->file_blangko_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_blangko_path)) {
                            $urlBlangko = route('cetak-blangko', $record);
                            $html .= '<div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="font-bold text-lg mb-1">Blangko Cuti Final</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Formulir Permintaan dan Pemberian Cuti</p>
                                <div class="flex gap-2">
                                    <a href="'.$urlBlangko.'" target="_blank" style="background-color: rgb(217 119 6); padding: 0.5rem 1rem; border-radius: 0.5rem; color: white; font-weight: bold; text-decoration: none; display: inline-block;">
                                        Download / Preview Blangko Cuti
                                    </a>
                                </div>
                            </div>';
                        } else {
                            $html .= '<div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="font-bold text-lg mb-1">Blangko Cuti</h4>
                                <p class="text-sm text-yellow-600">Dokumen sedang dipersiapkan...</p>
                            </div>';
                        }

                        if ($blangko && $blangko->status === 'disetujui') {
                            if ($blangko->file_surat_izin_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_surat_izin_path)) {
                                $urlSurat = route('cetak-surat-izin-cuti', $record);
                                $html .= '<div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                    <h4 class="font-bold text-lg mb-1">'.$suratIzinName.'</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Surat Izin Cuti sesuai kategori</p>
                                    <div class="flex gap-2">
                                        <a href="'.$urlSurat.'" target="_blank" style="background-color: rgb(217 119 6); padding: 0.5rem 1rem; border-radius: 0.5rem; color: white; font-weight: bold; text-decoration: none; display: inline-block;">
                                            Download / Preview Surat Izin Cuti
                                        </a>
                                    </div>
                                </div>';
                            } else {
                                $html .= '<div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                    <h4 class="font-bold text-lg mb-1">'.$suratIzinName.'</h4>
                                    <p class="text-sm text-red-500">Dokumen Surat Izin Cuti belum tersedia.</p>
                                </div>';
                            }
                        }
                        
                        $html .= '</div>';
                        
                        return new \Illuminate\Support\HtmlString($html);
                    }),

                DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole(['super_admin', 'admin'])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
