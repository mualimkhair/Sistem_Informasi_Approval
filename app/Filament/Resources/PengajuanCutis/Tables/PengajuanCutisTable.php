<?php

namespace App\Filament\Resources\PengajuanCutis\Tables;

use App\Exports\PengajuanCutiExport;
use App\Models\PengajuanCuti;
use App\Services\CutiService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PengajuanCutisTable
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
                            'Rekap-Pengajuan-Cuti.xlsx'
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
                        'disetujui' => 'Disetujui',
                        'ditolak_kanit' => 'Ditolak Kanit',
                        'ditolak_kasubag' => 'Ditolak Kasubag',
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
                        'menunggu_atasan',
                        'menunggu_kepala_unit', 'menunggu_kepala_seksi',
                        'menunggu_kanit_kepegawaian', 'menunggu_kasubag_tu' => 'warning',
                        'disetujui' => 'success',
                        'ditolak_kanit', 'ditolak_kasubag',
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
                            'tahunan' => 'Surat Izin Cuti Tahunan',
                            'besar' => 'Surat Izin Cuti Besar',
                            'sakit' => 'Surat Izin Cuti Sakit',
                            'melahirkan' => 'Surat Izin Cuti Bersalin',
                            'alasan_penting' => 'Surat Izin Cuti Alasan Penting',
                            'diluar_tanggungan_negara' => 'Surat Izin Cuti di Luar Tanggungan Negara',
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
                Action::make('tangguhkan')
                    ->label('Tangguhkan')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Tangguhkan Pengajuan Cuti')
                    ->modalDescription('Pengajuan cuti berstatus Disetujui akan ditangguhkan dan saldo cuti pegawai akan dikembalikan. Keputusan para pejabat pada PDF tetap tervalidasi sebagai riwayat.')
                    ->form([
                        Textarea::make('alasan')
                            ->label('Alasan Penangguhan')
                            ->placeholder('Opsional')
                            ->rows(3),
                    ])
                    ->visible(fn (PengajuanCuti $record) => $record->status === 'disetujui' && auth()->user()->hasRole(['super_admin', 'admin']))
                    ->action(function (PengajuanCuti $record, array $data): void {
                        try {
                            DB::transaction(fn () => CutiService::tangguhkanPengajuan(
                                $record,
                                filled($data['alasan'] ?? null) ? $data['alasan'] : null
                            ));

                            Notification::make()
                                ->title('Pengajuan Cuti Ditangguhkan')
                                ->body('Saldo cuti pegawai telah dikembalikan.')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Gagal Menangguhkan Pengajuan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
