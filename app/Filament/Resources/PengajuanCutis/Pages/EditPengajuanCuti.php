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
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $data = $this->form->getState();
        $saldo = Auth::user()->saldoCuti;

        if (!$saldo) return;

        $jenis = $data['jenis_cuti'];
        $lama = (int) ($data['lama_cuti'] ?? 0);

        if ($lama <= 0 || $jenis === 'diluar_tanggungan_negara') return;

        $totalSaldo = 0;
        if ($jenis === 'tahunan') {
            $totalSaldo = $saldo->saldo_n2 + $saldo->saldo_n1 + $saldo->saldo_n;
        } elseif (in_array($jenis, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
            $field = 'saldo_cuti_' . $jenis;
            $totalSaldo = $saldo->{$field} ?? 0;
        }

        if ($totalSaldo < $lama) {
            Notification::make()
                ->title('Saldo Cuti Tidak Mencukupi')
                ->body('Sisa saldo ' . str_replace('_', ' ', $jenis) . ' Anda (' . $totalSaldo . ' hari) tidak mencukupi untuk mengajukan ' . $lama . ' hari cuti.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
