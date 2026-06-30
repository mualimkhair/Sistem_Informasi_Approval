<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SaldoCuti;
use Illuminate\Support\Facades\DB;

class ResetCutiCommand extends Command
{
    protected $signature = 'cuti:reset-all';
    protected $description = 'Reset saldo cuti tahunan dan jenis cuti lainnya every Jan 1st';

    public function handle()
    {
        DB::transaction(function () {
            $saldos = SaldoCuti::all();
            foreach ($saldos as $saldo) {
                $saldo->saldo_n2 = min($saldo->saldo_n1, 6);
                $saldo->saldo_n1 = min($saldo->saldo_n, 6);
                $saldo->saldo_n = 12;
                
                $saldo->saldo_cuti_besar = 90;
                $saldo->saldo_cuti_sakit = 365;
                $saldo->saldo_cuti_melahirkan = 90;
                $saldo->saldo_cuti_alasan_penting = 30;
                
                $saldo->tahun_berjalan = date('Y');
                $saldo->save();
            }
        });

        $this->info('Saldo cuti seluruh pegawai berhasil di-reset.');
    }
}
