<?php

namespace App\Filament\Resources\PengajuanCutis\Pages;

use App\Filament\Resources\PengajuanCutis\PengajuanCutiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditPengajuanCuti extends EditRecord
{
    protected static string $resource = PengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn() => auth()->user()->hasRole(['super_admin', 'admin']))
        ];
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();
        $user = $this->record->user;
        $saldo = $user->saldoCuti;

        if (!$saldo) return;

        $jenis = $data['jenis_cuti'];
        $lama = (int) ($data['lama_cuti'] ?? 0);

        if ($lama <= 0 || $jenis === 'diluar_tanggungan_negara') return;

        $lamaLama = $this->record->lama_cuti;
        if ($lama > $lamaLama) {
            $totalSaldo = \App\Services\CutiService::hitungSaldoTersedia($user, $jenis);
            
            $tambahan = $lama - $lamaLama;

            if ($totalSaldo < $tambahan) {
                Notification::make()
                    ->title('Saldo Cuti Tidak Mencukupi')
                    ->body('Sisa saldo ' . str_replace('_', ' ', $jenis) . ' (' . $totalSaldo . ' hari) tidak mencukupi untuk tambahan ' . $tambahan . ' hari cuti.')
                    ->danger()
                    ->send();

                $this->halt();
            }
        }
    }

    protected function afterSave(): void
    {
        if ($this->record->status === 'menunggu_atasan') {
            \App\Services\CutiService::holdSaldo($this->record);
        }
    }
}
