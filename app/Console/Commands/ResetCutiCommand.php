<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SaldoCuti;
use Illuminate\Support\Facades\DB;
use App\Services\CutiService;

class ResetCutiCommand extends Command
{
    protected $signature = 'cuti:reset-all';
    protected $description = 'Reset saldo cuti tahunan dan jenis cuti lainnya every Jan 1st';

    public function handle()
    {
        DB::transaction(function () {
            $saldos = SaldoCuti::all();
            foreach ($saldos as $saldo) {
                CutiService::rolloverSaldoTahunan($saldo);
            }
        });
        $this->info('Rollover saldo cuti tahunan selesai.');
    }
}
