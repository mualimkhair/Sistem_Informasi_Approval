<?php

namespace App\Filament\Resources\PengajuanCutis\Pages;

use App\Filament\Resources\PengajuanCutis\PengajuanCutiResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListPengajuanCutis extends ListRecords
{
    protected static string $resource = PengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('rollover_saldo')
                ->label('Rollover Saldo Tahunan')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Jalankan Rollover Saldo Tahunan?')
                ->modalDescription(function () {
                    $sudahDijalankan = \App\Models\SaldoCuti::where('last_rollover_year', now()->year)->exists();
                    if ($sudahDijalankan) {
                        return 'PERHATIAN: Rollover sudah pernah dijalankan untuk beberapa/semua pegawai di tahun ini. Sistem akan melewati pegawai yang sudah dirollover.';
                    }
                    return 'Ini akan menjalankan rollover saldo tahunan untuk SELURUH pegawai. N2 akan hangus, N1 → N2, N → N1, N baru = 12.';
                })
                ->visible(fn() => auth()->user()->hasRole(['super_admin', 'admin']))
                ->action(function () {
                    $berhasil = 0;
                    $dilewati = 0;

                    \Illuminate\Support\Facades\DB::transaction(function () use (&$berhasil, &$dilewati) {
                        $saldos = \App\Models\SaldoCuti::with('user')->get();
                        $tahunSekarang = now()->year;

                        foreach ($saldos as $saldo) {
                            if ($saldo->user && !$saldo->user->hasRole('super_admin')) {
                                if ($saldo->last_rollover_year == $tahunSekarang) {
                                    $dilewati++;
                                    continue;
                                }
                                \App\Services\CutiService::rolloverSaldoTahunan($saldo);
                                $berhasil++;
                            }
                        }
                    });

                    \Filament\Notifications\Notification::make()
                        ->title("Rollover Selesai")
                        ->body("Berhasil: {$berhasil} pegawai. Dilewati: {$dilewati} pegawai.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
