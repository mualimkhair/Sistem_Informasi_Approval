<?php

namespace App\Filament\Resources\AuditPengajuanCutis\Pages;

use App\Filament\Resources\AuditPengajuanCutis\AuditPengajuanCutiResource;
use App\Models\PengajuanCuti;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListAuditPengajuanCutis extends ListRecords
{
    protected static string $resource = AuditPengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nama')->label('Pegawai')->searchable()->sortable(),
                TextColumn::make('user.unitKerja.nama_unit')->label('Unit Kerja')->searchable(),
                TextColumn::make('jenis_cuti')->badge()->sortable(),
                TextColumn::make('tanggal_mulai')->date()->sortable(),
                TextColumn::make('tanggal_selesai')->date()->sortable(),
                TextColumn::make('lama_cuti')->label('Lama')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'menunggu_atasan', 'menunggu_pejabat' => 'warning',
                        'disetujui' => 'success',
                        'ditolak_kanit', 'ditolak_kasubag', 'ditolak_pejabat' => 'danger',
                        'ditangguhkan', 'perubahan' => 'gray',
                        'dihapus' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'menunggu_atasan' => 'Menunggu Atasan',
                        'menunggu_pejabat' => 'Menunggu Pejabat',
                        'disetujui' => 'Disetujui',
                        'ditolak_kanit' => 'Ditolak Kanit',
                        'ditolak_kasubag' => 'Ditolak Kasubag',
                        'ditolak_pejabat' => 'Ditolak Pejabat',
                        'perubahan' => 'Perlu Perubahan',
                        'ditangguhkan' => 'Ditangguhkan',
                        'dihapus' => 'Dihapus',
                        default => ucwords(str_replace('_', ' ', $state)),
                    }),
                BadgeColumn::make('deleted_at')
                    ->label('')
                    ->formatStateUsing(fn($state) => $state ? 'DIHAPUS' : null)
                    ->color('danger')
                    ->visible(fn() => true),
                TextColumn::make('created_at')->label('Diajukan')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'menunggu_atasan' => 'Menunggu Atasan',
                        'menunggu_pejabat' => 'Menunggu Pejabat',
                        'disetujui' => 'Disetujui',
                        'ditolak_kanit' => 'Ditolak Kanit',
                        'ditolak_kasubag' => 'Ditolak Kasubag',
                        'ditolak_pejabat' => 'Ditolak Pejabat',
                        'perubahan' => 'Perlu Perubahan',
                        'ditangguhkan' => 'Ditangguhkan',
                    ]),
                Filter::make('deleted_at')
                    ->label('Termasuk Dihapus')
                    ->query(fn(Builder $query) => $query->withTrashed()),
            ])
            ->actions([
                Action::make('riwayat')
                    ->label('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->modalHeading(fn(PengajuanCuti $record) => 'Riwayat: ' . $record->user->nama . ' - ' . str_replace('_', ' ', $record->jenis_cuti))
                    ->modalContent(function (PengajuanCuti $record) {
                        $record->load(['statusLogs.changedBy', 'ledgers']);

                        return view('audit.riwayat-modal', [
                            'pengajuan' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

            ]);
    }
}
