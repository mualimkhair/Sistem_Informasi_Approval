<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PdfController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware(['auth'])->group(function () {
    Route::get('/pengajuan-cuti/{pengajuanCuti}/pdf', [PdfController::class, 'cetak'])->name('pengajuan-cuti.pdf');
    Route::get('/users/template', function () {
        $export = new class implements
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\WithEvents
        {
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
                    'Saldo Cuti Alasan Penting',
                ];
            }

            public function registerEvents(): array {
                return [
                    \Maatwebsite\Excel\Events\AfterSheet::class => function (\Maatwebsite\Excel\Events\AfterSheet $event) {
                        $sheet = $event->sheet->getDelegate();
                        $sheet->getColumnDimension('A')->setWidth(22);
                        $highestRow = 100;
                        for ($row = 2; $row <= $highestRow; $row++) {
                            $sheet->getStyle("A{$row}")
                                ->getNumberFormat()
                                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
                        }
                    },
                ];
            }
        };
        return \Maatwebsite\Excel\Facades\Excel::download($export, 'Template-Pegawai.xlsx');
    })->name('users.template');
});
