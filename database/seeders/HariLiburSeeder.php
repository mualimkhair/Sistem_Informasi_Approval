<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HariLiburSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('hari_liburs')->delete();

        $liburNasional = [
            ['tanggal' => '2026-01-01', 'keterangan' => 'Tahun Baru 2026 Masehi', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-01-16', 'keterangan' => 'Isra Mikraj Nabi Muhammad SAW', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-02-17', 'keterangan' => 'Tahun Baru Imlek 2577 Kongzili', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-03-19', 'keterangan' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-03-21', 'keterangan' => 'Idul Fitri 1447 H (hari 1)', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-03-22', 'keterangan' => 'Idul Fitri 1447 H (hari 2)', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-04-03', 'keterangan' => 'Wafat Yesus Kristus', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-04-05', 'keterangan' => 'Kebangkitan Yesus Kristus (Paskah)', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-05-01', 'keterangan' => 'Hari Buruh Internasional', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-05-14', 'keterangan' => 'Kenaikan Yesus Kristus', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-05-27', 'keterangan' => 'Idul Adha 1447 H', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-05-31', 'keterangan' => 'Hari Raya Waisak 2570 BE', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-06-01', 'keterangan' => 'Hari Lahir Pancasila', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-06-16', 'keterangan' => '1 Muharram Tahun Baru Islam 1448 H', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-08-17', 'keterangan' => 'Proklamasi Kemerdekaan', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-08-25', 'keterangan' => 'Maulid Nabi Muhammad SAW', 'jenis' => 'libur_nasional'],
            ['tanggal' => '2026-12-25', 'keterangan' => 'Kelahiran Yesus Kristus', 'jenis' => 'libur_nasional'],
        ];

        $cutiBersama = [
            ['tanggal' => '2026-02-16', 'keterangan' => 'Cuti Bersama Tahun Baru Imlek', 'jenis' => 'cuti_bersama'],
            ['tanggal' => '2026-03-18', 'keterangan' => 'Cuti Bersama Hari Suci Nyepi', 'jenis' => 'cuti_bersama'],
            ['tanggal' => '2026-03-20', 'keterangan' => 'Cuti Bersama Idul Fitri (hari 1)', 'jenis' => 'cuti_bersama'],
            ['tanggal' => '2026-03-23', 'keterangan' => 'Cuti Bersama Idul Fitri (hari 2)', 'jenis' => 'cuti_bersama'],
            ['tanggal' => '2026-03-24', 'keterangan' => 'Cuti Bersama Idul Fitri (hari 3)', 'jenis' => 'cuti_bersama'],
            ['tanggal' => '2026-05-15', 'keterangan' => 'Cuti Bersama Kenaikan Yesus Kristus', 'jenis' => 'cuti_bersama'],
            ['tanggal' => '2026-05-28', 'keterangan' => 'Cuti Bersama Idul Adha', 'jenis' => 'cuti_bersama'],
            ['tanggal' => '2026-12-24', 'keterangan' => 'Cuti Bersama Kelahiran Yesus Kristus', 'jenis' => 'cuti_bersama'],
        ];

        $allHolidays = array_merge($liburNasional, $cutiBersama);
        $now = now();
        
        foreach ($allHolidays as &$holiday) {
            $holiday['created_at'] = $now;
            $holiday['updated_at'] = $now;
        }

        DB::table('hari_liburs')->insert($allHolidays);
    }
}
