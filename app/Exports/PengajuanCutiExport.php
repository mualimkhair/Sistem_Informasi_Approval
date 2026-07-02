<?php

namespace App\Exports;

use App\Models\PengajuanCuti;
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
            $pengajuan->tanggal_mulai ? \Carbon\Carbon::parse($pengajuan->tanggal_mulai)->format('d-m-Y') : '-',
            $pengajuan->tanggal_selesai ? \Carbon\Carbon::parse($pengajuan->tanggal_selesai)->format('d-m-Y') : '-',
            $pengajuan->lama_cuti,
            strtoupper($pengajuan->status),
            $pengajuan->keputusan_kanit ?? '-',
            $pengajuan->keputusan_kasubag ?? '-',
            $pengajuan->keputusan_pejabat ?? '-',
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
            'Keputusan Kanit',
            'Keputusan Kasubag',
            'Keputusan Pejabat',
        ];
    }
}
