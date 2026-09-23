<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

use Illuminate\Support\Enumerable;

class PengajuanCutiExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection(): Enumerable
    {
        return $this->query->with([
            'user.unitKerja',
            'user.seksi',
            'unitKerja.kepalaUnit',
            'seksi.kepalaSeksi',
            'blangkoCuti.kabandara'
        ])->get();
    }

    public function map($pengajuan): array
    {
        $isOperasional = $pengajuan->tipe_aliran === 'operasional';
        $isAdministrasi = $pengajuan->tipe_aliran === 'administrasi';

        // Kepala Unit (Operasional)
        $kepalaUnit = $isOperasional 
            ? ($pengajuan->kepala_unit_nama ?? $pengajuan->kepalaUnit?->nama ?? '-') 
            : '-';
        $keputusanKepalaUnit = $isOperasional ? ($pengajuan->keputusan_kepala_unit ?? '-') : '-';

        // Kepala Seksi (Operasional)
        $kepalaSeksi = $isOperasional 
            ? ($pengajuan->kepala_seksi_nama ?? $pengajuan->kepalaSeksi?->nama ?? '-') 
            : '-';
        $keputusanKepalaSeksi = $isOperasional ? ($pengajuan->keputusan_kepala_seksi ?? '-') : '-';

        // Kanit Kepegawaian (Operasional)
        $kanitKepegawaian = $isOperasional 
            ? ($pengajuan->kanit_kepegawaian_nama ?? $pengajuan->kanitKepegawaian?->nama ?? '-') 
            : '-';
        $keputusanKanitKepegawaian = $isOperasional ? ($pengajuan->keputusan_kanit_kepegawaian ?? '-') : '-';

        // Kasubag TU (Operasional)
        $kasubagTu = $isOperasional 
            ? ($pengajuan->kasubag_tu_nama ?? $pengajuan->kasubagTu?->nama ?? '-') 
            : '-';
        $keputusanKasubagTu = $isOperasional ? ($pengajuan->keputusan_kasubag_tu ?? '-') : '-';

        return [
            $pengajuan->user->nama ?? '-',
            $pengajuan->user->nip ?? '-',
            $pengajuan->user->jabatan ?? '-',
            $pengajuan->user->unitKerja->nama_unit ?? '-',
            $pengajuan->user->seksi->nama_seksi ?? '-',
            ucwords(str_replace('_', ' ', $pengajuan->tipe_aliran ?? 'administrasi')),
            strtoupper(str_replace('_', ' ', $pengajuan->jenis_cuti)),
            $pengajuan->tanggal_mulai ? Carbon::parse($pengajuan->tanggal_mulai)->format('d-m-Y') : '-',
            $pengajuan->tanggal_selesai ? Carbon::parse($pengajuan->tanggal_selesai)->format('d-m-Y') : '-',
            $pengajuan->lama_cuti,
            $kepalaUnit,
            $keputusanKepalaUnit,
            $kepalaSeksi,
            $keputusanKepalaSeksi,
            $kanitKepegawaian,
            $keputusanKanitKepegawaian,
            $kasubagTu,
            $keputusanKasubagTu,
            $pengajuan->blangkoCuti?->kabandara_nama ?? $pengajuan->blangkoCuti?->kabandara?->nama ?? '-',
            $pengajuan->blangkoCuti?->tanggal_keputusan ? Carbon::parse($pengajuan->blangkoCuti->tanggal_keputusan)->format('d-m-Y') : '-',
            $pengajuan->blangkoCuti?->status ?? '-',
            $pengajuan->final_business_status,
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Pegawai',
            'NIP',
            'Jabatan',
            'Unit Kerja',
            'Seksi',
            'Aliran',
            'Jenis Cuti',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Lama Cuti (Hari)',
            'Kepala Unit',
            'Keputusan Kepala Unit',
            'Kepala Seksi',
            'Keputusan Kepala Seksi',
            'Kanit Kepegawaian',
            'Keputusan Kanit Kepegawaian',
            'Kasubag TU',
            'Keputusan Kasubag TU',
            'Kabandara',
            'Tanggal Keputusan Kabandara',
            'Keputusan Kabandara',
            'Status Akhir',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = 'V';

                // 1. Column Widths
                $widths = [
                    'A' => 25, 'B' => 18, 'C' => 25, 'D' => 25, 'E' => 20, 
                    'F' => 15, 'G' => 20, 'H' => 15, 'I' => 15, 'J' => 12,
                    'K' => 25, 'L' => 18, 'M' => 25, 'N' => 18,
                    'O' => 25, 'P' => 18, 'Q' => 25, 'R' => 18,
                    'S' => 25, 'T' => 18, 'U' => 18, 'V' => 22
                ];
                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // 2. Header Styling (A1:V1)
                $headerRange = "A1:{$highestColumn}1";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                        'size' => 10,
                        'name' => 'Arial'
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF1F4E78']
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(40);

                // 3. Body Styling (A2:V$highestRow)
                if ($highestRow > 1) {
                    $bodyRange = "A2:{$highestColumn}{$highestRow}";
                    $sheet->getStyle($bodyRange)->applyFromArray([
                        'font' => [
                            'size' => 9,
                            'name' => 'Arial'
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);

                    // Alignments
                    $leftCols = ['A', 'C', 'D', 'E', 'K', 'M', 'O', 'Q', 'S'];
                    foreach ($leftCols as $col) {
                        $sheet->getStyle("{$col}2:{$col}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }

                    $centerCols = ['B', 'F', 'G', 'H', 'I', 'J', 'L', 'N', 'P', 'R', 'T', 'U', 'V'];
                    foreach ($centerCols as $col) {
                        $sheet->getStyle("{$col}2:{$col}{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }
                }

                // 4. AutoFilter
                $sheet->setAutoFilter("A1:{$highestColumn}{$highestRow}");

                // 5. Freeze Pane
                $sheet->freezePane('A2');

                // 6. Page Setup / Print Layout
                $pageSetup = $sheet->getPageSetup();
                $pageSetup->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
                $pageSetup->setFitToWidth(1);
                $pageSetup->setFitToHeight(0); // Unlimited height
                
                // Repeat header row
                $pageSetup->setRowsToRepeatAtTopByStartAndEnd(1, 1);
            }
        ];
    }
}
