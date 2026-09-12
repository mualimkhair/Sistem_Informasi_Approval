<?php

namespace App\Filament\Resources\BlangkoCutis;

use App\Filament\Resources\BlangkoCutis\Pages\ManageBlangkoCutis;
use App\Models\BlangkoCuti;
use App\Models\PengajuanCuti;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Enums\FiltersLayout;

class BlangkoCutiResource extends Resource
{
    protected static ?string $model = BlangkoCuti::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Persetujuan Blangko Cuti';

    protected static ?string $modelLabel = 'Persetujuan Blangko Cuti';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('pejabat_berwenang');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->hasRole(['super_admin', 'admin', 'pejabat_berwenang']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pengajuanCuti.user.nama')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pengajuanCuti.user.unitKerja.nama_unit')
                    ->label('Unit Kerja')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pengajuanCuti.jenis_cuti')
                    ->label('Jenis Cuti')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pengajuanCuti.lama_cuti')
                    ->label('Lama Cuti (Hari)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pengajuanCuti.tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('pengajuanCuti.tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date('d M Y')
                    ->sortable(),
                BadgeColumn::make('pengajuanCuti.status')
                    ->label('Status Pengajuan')
                    ->colors([
                        'warning' => 'menunggu_atasan', 'menunggu_pejabat', 'menunggu_kepala_unit', 'menunggu_kepala_seksi', 'menunggu_kanit_kepegawaian', 'menunggu_kasubag_tu',
                        'success' => 'disetujui',
                        'danger' => 'ditolak_kanit', 'ditolak_kasubag', 'ditolak_pejabat', 'ditolak_kepala_unit', 'ditolak_kepala_seksi', 'ditolak_kanit_kepegawaian', 'ditolak_kasubag_tu',
                        'gray' => 'ditangguhkan', 'perubahan'
                    ])
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->label('Status Blangko')
                    ->colors([
                        'warning' => 'menunggu',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->label('Detail')
                    ->infolist([
                        TextEntry::make('pengajuanCuti.user.nama')->label('Pegawai'),
                        TextEntry::make('pengajuanCuti.user.unitKerja.nama_unit')->label('Unit Kerja'),
                        TextEntry::make('pengajuanCuti.jenis_cuti')->label('Jenis Cuti')->badge(),
                        TextEntry::make('pengajuanCuti.alasan')->label('Alasan Cuti'),
                        TextEntry::make('pengajuanCuti.lama_cuti')->label('Lama Cuti (Hari)'),
                        TextEntry::make('pengajuanCuti.tanggal_mulai')->label('Tanggal Mulai')->date('d M Y'),
                        TextEntry::make('pengajuanCuti.tanggal_selesai')->label('Tanggal Selesai')->date('d M Y'),
                        TextEntry::make('pengajuanCuti.status')->label('Status Pengajuan Internal')->badge(),
                        TextEntry::make('status')->label('Status Blangko')->badge(),
                        TextEntry::make('alasan')->label('Alasan Keputusan Kabandara'),
                    ]),
                Action::make('approval')
                    ->label('Approval Kabandara')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (BlangkoCuti $record) => $record->status === 'menunggu' && auth()->user()->hasRole('pejabat_berwenang'))
                    ->form([
                        Select::make('status')
                            ->label('Keputusan')
                            ->options([
                                'disetujui' => 'Setujui',
                                'ditolak' => 'Tolak',
                            ])
                            ->required()
                            ->reactive(),
                        Textarea::make('alasan')
                            ->label('Catatan Tambahan')
                            ->required(fn ($get) => $get('status') === 'ditolak'),
                    ])
                    ->action(function (BlangkoCuti $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $user = auth()->user();
                            $record->update([
                                'status' => $data['status'],
                                'alasan' => $data['alasan'] ?? null,
                                'tanggal_keputusan' => now(),
                                'kabandara_id' => $user->id,
                                'kabandara_nama' => $user->nama,
                                'kabandara_nip' => $user->nip,
                                'kabandara_pangkat' => $user->pangkat,
                            ]);
                            
                            \App\Services\CutiService::generateAndSaveFinalDocuments($record);
                            $record->refresh();
                            
                            if ($data['status'] === 'disetujui') {
                                Notification::make()
                                    ->title('Pengajuan Cuti Telah Disetujui')
                                    ->body('Pengajuan cuti Anda telah selesai diproses dan telah disetujui oleh Kabandara. Dokumen final telah tersedia di sistem.')
                                    ->actions([
                                        \Filament\Actions\Action::make('cetak_blangko')
                                            ->label('Lihat / Download Blangko Cuti')
                                            ->url(route('cetak-blangko', $record->pengajuan_cuti_id))
                                            ->button()
                                            ->color('success')
                                            ->openUrlInNewTab(),
                                        \Filament\Actions\Action::make('cetak_surat')
                                            ->label('Lihat / Download Surat Izin Cuti')
                                            ->url(route('cetak-surat-izin-cuti', $record->pengajuan_cuti_id))
                                            ->button()
                                            ->openUrlInNewTab(),
                                    ])
                                    ->info()
                                    ->sendToDatabase($record->pengajuanCuti->user);
                            } else {
                                Notification::make()
                                    ->title('Blangko Cuti ' . ucfirst($data['status']))
                                    ->body('Blangko cuti Anda telah ' . $data['status'] . ' oleh Kabandara.' . (!empty($data['alasan']) ? ' Alasan: ' . $data['alasan'] : ''))
                                    ->actions([
                                        \Filament\Actions\Action::make('cetak_blangko')
                                            ->label('Lihat / Download Blangko Cuti')
                                            ->url(route('cetak-blangko', $record->pengajuan_cuti_id))
                                            ->button()
                                            ->color('danger')
                                            ->openUrlInNewTab(),
                                    ])
                                    ->info()
                                    ->sendToDatabase($record->pengajuanCuti->user);
                            }
                        });
                        
                        Notification::make()->title('Keputusan berhasil disimpan.')->success()->send();
                    }),
                    
                Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->visible(fn ($record) => in_array($record->status, ['disetujui', 'ditolak']) || auth()->user()->hasRole(['super_admin', 'admin']))
                    ->modalHeading('DOKUMEN CUTI')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Tutup'))
                    ->modalContent(function ($record) {
                        $blangko = $record;
                        $pengajuan = $record->pengajuanCuti;
                        
                        // Auto-recovery for missing Blangko Cuti PDF (e.g. historical records)
                        if ($blangko && (!$blangko->file_blangko_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_blangko_path))) {
                            \App\Services\CutiService::generateAndSaveFinalDocuments($blangko);
                            $blangko->refresh();
                        }
                        
                        $suratIzinName = match($pengajuan->jenis_cuti) {
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
                            $urlBlangko = route('cetak-blangko', $pengajuan->id);
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
                                $urlSurat = route('cetak-surat-izin-cuti', $pengajuan->id);
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
                                fn (Builder $query, $date): Builder => $query->whereHas('pengajuanCuti', fn($q) => $q->whereDate('tanggal_mulai', '>=', $date)),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereHas('pengajuanCuti', fn($q) => $q->whereDate('tanggal_selesai', '<=', $date)),
                            );
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBlangkoCutis::route('/'),
        ];
    }
}
