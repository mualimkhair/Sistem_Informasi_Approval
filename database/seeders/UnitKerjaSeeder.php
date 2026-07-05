<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Seksi;
use App\Models\UnitKerja;
use App\Models\User;

class UnitKerjaSeeder extends Seeder
{
    public function run(): void
    {
        $seksisData = [
            'Kepala Seksi Kampen dan Pelayanan Darurat' => [
                'Unit Kargo', 'Unit Terminal dan Pengamanan Kampen', 'Unit Proteksi', 'Unit PKP-PK'
            ],
            'Kepala Seksi Pelayanan dan Kerjasama' => [
                'Koordinator Kerjasama', 'Koordinator Informasi', 'Unit Terminal', 'Unit Hygiene dan Sanitasi'
            ],
            'Kepala Seksi Teknik dan Operasi' => [
                'Unit Fasilitas Elektronika', 'Unit AMC', 'Unit Elektrikal Mekanikal', 'Unit Bangunan', 'Unit Alat Alat Besar', 'Unit Landasan'
            ],
            'Kasubag Keuangan dan Tata Usaha' => [
                'Koordinator Perencanaan dan Program', 'Koordinator Kepegawaian', 'Koordinator Teknologi dan Humas', 'Koordinator Keuangan', 'Koordinator PPID', 'Koordinator Pengevaluasi dan Penyusunan Laporan', 'Ketua SPI', 'Koordinator BMN', 'Unit Tata Usaha'
            ]
        ];

        foreach ($seksisData as $namaSeksi => $units) {
            $oldUnitSeksiNames = [];
            if (str_contains($namaSeksi, 'Kampen')) {
                $oldUnitSeksiNames[] = 'Kasi Kampen dan Pelayanan Darurat';
            } elseif (str_contains($namaSeksi, 'Pelayanan dan Kerjasama')) {
                $oldUnitSeksiNames[] = 'Kasi Pelayanan dan Kerjasama';
            } elseif (str_contains($namaSeksi, 'Teknik dan Operasi')) {
                $oldUnitSeksiNames[] = 'Kasi Teknik dan Operasi';
            } elseif (str_contains($namaSeksi, 'Keuangan dan Tata Usaha')) {
                $oldUnitSeksiNames[] = 'KSTU';
            }

            $seksi = Seksi::firstOrCreate(['nama_seksi' => $namaSeksi]);
            
            if (!empty($oldUnitSeksiNames)) {
                $oldUnits = DB::table('unit_kerjas')->whereIn('nama_unit', $oldUnitSeksiNames)->get();
                foreach ($oldUnits as $ou) {
                    $kasiUsers = User::where('unit_kerja_id', $ou->id)->get();
                    foreach ($kasiUsers as $ku) {
                        $ku->seksi_id = $seksi->id;
                        $ku->unit_kerja_id = null;
                        $ku->save();
                        
                        if (is_null($seksi->kepala_seksi_id)) {
                            $seksi->update(['kepala_seksi_id' => $ku->id]);
                        }
                    }
                }
            }

            foreach ($units as $namaUnit) {
                $existingUnitQuery = UnitKerja::where('nama_unit', $namaUnit);
                
                if ($namaUnit === 'Koordinator Keuangan') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit Keuangan')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Koordinator Perencanaan dan Program') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit Perencanaan')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Koordinator Kepegawaian') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit Kepegawaian')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Koordinator Teknologi dan Humas') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit Humas')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Koordinator Kerjasama') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit Kerjasama')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Koordinator Informasi') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit Informasi')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Koordinator PPID') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit PPID')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Koordinator Pengevaluasi dan Penyusunan Laporan') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit Pengevaluasi dan Penyusun Laporan')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Ketua SPI') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'SPI')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Koordinator BMN') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit BMN')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Unit Alat Alat Besar') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit A2B')->orWhere('nama_unit', $namaUnit);
                } elseif ($namaUnit === 'Unit Elektrikal Mekanikal') {
                    $existingUnitQuery = UnitKerja::where('nama_unit', 'Unit Listrik')->orWhere('nama_unit', $namaUnit);
                }

                $existingUnit = $existingUnitQuery->first();

                if ($existingUnit) {
                    $existingUnit->update([
                        'nama_unit' => $namaUnit,
                        'seksi_id' => $seksi->id,
                    ]);
                } else {
                    UnitKerja::create([
                        'nama_unit' => $namaUnit,
                        'jenis' => 'administrasi',
                        'seksi_id' => $seksi->id,
                    ]);
                }
            }
        }

        $bendaharaUnit = DB::table('unit_kerjas')->where('nama_unit', 'Bendahara Penerima')->first();
        if ($bendaharaUnit) {
            $keuanganUnit = UnitKerja::where('nama_unit', 'Koordinator Keuangan')->first();
            if ($keuanganUnit) {
                User::where('unit_kerja_id', $bendaharaUnit->id)->update([
                    'unit_kerja_id' => $keuanganUnit->id,
                    'seksi_id' => $keuanganUnit->seksi_id
                ]);
            }
        }

        $sekretarisUnit = DB::table('unit_kerjas')->where('nama_unit', 'Sekretaris Kabandara')->first();
        if ($sekretarisUnit) {
            $tuUnit = UnitKerja::where('nama_unit', 'Unit Tata Usaha')->first();
            if ($tuUnit) {
                User::where('unit_kerja_id', $sekretarisUnit->id)->update([
                    'unit_kerja_id' => $tuUnit->id,
                    'seksi_id' => $tuUnit->seksi_id
                ]);
            }
        }

        $usersToUpdate = User::whereNotNull('unit_kerja_id')->whereNull('seksi_id')->get();
        foreach ($usersToUpdate as $u) {
            $uk = UnitKerja::find($u->unit_kerja_id);
            if ($uk && $uk->seksi_id) {
                $u->update(['seksi_id' => $uk->seksi_id]);
            }
        }

        UnitKerja::whereNull('seksi_id')->delete();
    }
}
