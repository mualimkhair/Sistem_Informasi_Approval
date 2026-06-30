<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanCuti;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Carbon\Carbon;

class PegawaiSedangCutiWidget extends BaseWidget
{
    protected static ?string $heading = 'Pegawai Sedang Cuti Saat Ini';
    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()->hasRole('pejabat_berwenang');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PengajuanCuti::query()
                    ->where('status', 'disetujui')
                    ->whereDate('tanggal_mulai', '<=', Carbon::now())
                    ->whereDate('tanggal_selesai', '>=', Carbon::now())
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.nama')->label('Pegawai'),
                Tables\Columns\TextColumn::make('user.unitKerja.nama_unit')->label('Unit Kerja'),
                Tables\Columns\TextColumn::make('jenis_cuti')->badge(),
                Tables\Columns\TextColumn::make('tanggal_selesai')->label('Sampai Tanggal')->date(),
            ])
            ->paginated(false);
    }
}
