<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PdfController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/pengajuan-cuti/{pengajuanCuti}/pdf', [PdfController::class, 'cetak'])->name('pengajuan-cuti.pdf');
    Route::get('/users/template', function () {
        $export = new class implements \Maatwebsite\Excel\Concerns\WithHeadings {
            public function headings(): array {
                return [
                    'NIP', 
                    'Nama',
                    'Saldo N',
                    'Saldo N-1',
                    'Saldo N-2',
                    'Saldo Cuti Besar',
                    'Saldo Cuti Sakit',
                    'Saldo Cuti Melahirkan',
                    'Saldo Cuti Alasan Penting'
                ];
            }
        };
        return \Maatwebsite\Excel\Facades\Excel::download($export, 'Template-Pegawai.xlsx');
    })->name('users.template');
});
