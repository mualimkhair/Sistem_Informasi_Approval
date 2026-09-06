<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PengajuanCutiExport implements FromCollection, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function collection()
    {
        return $this->query->get();
    }

    public function map($pengajuan): array
    {
        return [
            $pengajuan->user->nama ?? '-',
            $pengajuan->user->unitKerja->nama_unit ?? '-',
            strtoupper($pengajuan->jenis_cuti),
            $pengajuan->tanggal_mulai ? Carbon::parse($pengajuan->tanggal_mulai)->format('d-m-Y') : '-',
            $pengajuan->tanggal_selesai ? Carbon::parse($pengajuan->tanggal_selesai)->format('d-m-Y') : '-',
            $pengajuan->lama_cuti,
            strtoupper($pengajuan->status),
            ucwords(str_replace('_', ' ', $pengajuan->tipe_aliran ?? 'administrasi')),
            $pengajuan->keputusan_kanit ?? '-',
            $pengajuan->keputusan_kasubag ?? '-',
            $pengajuan->keputusan_pejabat ?? '-',
            $pengajuan->keputusan_kepala_unit ?? '-',
            $pengajuan->keputusan_kepala_seksi ?? '-',
            $pengajuan->keputusan_kanit_kepegawaian ?? '-',
            $pengajuan->keputusan_kasubag_tu ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'Nama Pegawai',
            'Unit Kerja',
            'Jenis Cuti',
            'Tanggal Mulai',
            'Tanggal Selesai',
            'Lama Cuti (Hari)',
            'Status Akhir',
            'Aliran',
            'Keputusan Kanit',
            'Keputusan Kasubag',
            'Keputusan Pejabat',
            'Keputusan Kepala Unit',
            'Keputusan Kepala Seksi',
            'Keputusan Kanit Kepegawaian',
            'Keputusan Kasubag TU',
        ];
    }
}
