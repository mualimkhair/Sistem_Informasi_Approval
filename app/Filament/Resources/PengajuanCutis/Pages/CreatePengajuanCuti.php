<?php

namespace App\Filament\Resources\PengajuanCutis\Pages;

use App\Filament\Resources\PengajuanCutis\PengajuanCutiResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreatePengajuanCuti extends CreateRecord
{
    protected static string $resource = PengajuanCutiResource::class;

    public function mount(): void
    {
        if (Auth::user()->hasRole('pejabat_berwenang')) {
            abort(403, 'Pengajuan cuti untuk Pejabat Berwenang diproses langsung melalui Bagian Kepegawaian.');
        }

        parent::mount();
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $saldo = Auth::user()->fresh()->saldoCuti;

        if (!$saldo) return;

        $jenis = $data['jenis_cuti'];
        $lama = (int) ($data['lama_cuti'] ?? 0);

        if ($lama <= 0 || $jenis === 'diluar_tanggungan_negara') return;

        $totalSaldo = \App\Services\CutiService::hitungSaldoTersedia(Auth::user(), $jenis);

        if ($totalSaldo < $lama) {
            Notification::make()
                ->title('Saldo Cuti Tidak Mencukupi')
                ->body('Sisa saldo ' . str_replace('_', ' ', $jenis) . ' Anda (' . $totalSaldo . ' hari) tidak mencukupi untuk mengajukan ' . $lama . ' hari cuti.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function afterCreate(): void
    {
        \App\Services\CutiService::holdSaldo($this->record);
    }
}

