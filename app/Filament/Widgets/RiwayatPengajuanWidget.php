<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanCuti;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RiwayatPengajuanWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PengajuanCuti::where('user_id', auth()->id())->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('jenis_cuti')->label('Jenis')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tanggal_mulai')->label('Mulai')->date(),
                TextColumn::make('tanggal_selesai')->label('Selesai')->date(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
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
            ]);
    }
}
