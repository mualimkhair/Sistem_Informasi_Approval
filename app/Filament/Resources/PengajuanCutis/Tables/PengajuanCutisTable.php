<?php

namespace App\Filament\Resources\PengajuanCutis\Tables;

use App\Exports\PengajuanCutiExport;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
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
