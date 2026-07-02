<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin',
            'admin',
            'pegawai',
            'kanit',
            'kasubag',
            'pejabat_berwenang'
        ];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $adminUnits = [
            'KSTU', 'Unit Pelayanan (PAS)', 'Unit Perencanaan', 'Unit Keuangan', 
            'Bendahara Penerima', 'SPI', 'Unit BMN', 'Unit Kepegawaian', 'Unit Humas', 
            'Unit Kerjasama', 'Unit PPID', 'Unit Pengevaluasi dan Penyusun Laporan', 'Sekretaris Kabandara'
        ];
        
        foreach ($adminUnits as $unit) {
            DB::table('unit_kerjas')->insert([
                'nama_unit' => $unit,
                'jenis' => 'administrasi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $opsUnits = [
            'Kasi Kampen dan Pelayanan Darurat', 'Kasi Teknik dan Operasi', 'Unit Landasan', 
            'Unit Informasi', 'Unit AMC', 'Unit PKP-PK', 'Unit Bangunan', 'Unit Fasilitas Elektronika', 
            'Unit Avsec', 'Kasi Pelayanan dan Kerjasama', 'Quality Control', 'Unit Terminal', 
            'Unit Elektrikal Mekanikal', 'Unit A2B', 'Unit Listrik', 'Unit Hygiene dan Sanitasi'
        ];
        
        foreach ($opsUnits as $unit) {
            DB::table('unit_kerjas')->insert([
                'nama_unit' => $unit,
                'jenis' => 'operasional',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $opsUnitId = DB::table('unit_kerjas')->where('jenis', 'operasional')->first()->id;
        DB::table('kelompok_kerjas')->insert([
            'unit_kerja_id' => $opsUnitId,
            'nama_kelompok' => 'Kelompok A',
            'hari_libur_1' => 'Sabtu',
            'hari_libur_2' => 'Minggu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $superAdmin = User::create([
            'nip' => '199001012020121001',
            'nama' => 'Super Admin',
            'password' => Hash::make('199001012020121001'),
            'unit_kerja_id' => 1,
            'is_profile_completed' => true,
        ]);
        $superAdmin->assignRole('super_admin');
        
        DB::table('saldo_cutis')->insert([
            'user_id' => $superAdmin->id,
            'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0,
            'tahun_berjalan' => date('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $pegawai = User::create([
            'nip' => '199202022020121002',
            'nama' => 'Pegawai Operasional',
            'password' => Hash::make('199202022020121002'),
            'unit_kerja_id' => $opsUnitId,
            'is_profile_completed' => true,
        ]);
        $pegawai->assignRole('pegawai');
        
        DB::table('saldo_cutis')->insert([
            'user_id' => $pegawai->id,
            'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0,
            'tahun_berjalan' => date('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $kanit = User::create([
            'nip' => '198003032010121003',
            'nama' => 'Bapak Kanit',
            'password' => Hash::make('198003032010121003'),
            'unit_kerja_id' => $opsUnitId,
            'is_profile_completed' => true,
        ]);
        $kanit->assignRole('kanit');
        $kanit->assignRole('pegawai');

        DB::table('saldo_cutis')->insert([
            'user_id' => $kanit->id,
            'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0,
            'tahun_berjalan' => date('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $kasubag = User::create([
            'nip' => '197004042000121004',
            'nama' => 'Ibu Kasubag',
            'password' => Hash::make('197004042000121004'),
            'unit_kerja_id' => $opsUnitId,
            'is_profile_completed' => true,
        ]);
        $kasubag->assignRole('kasubag');
        $kasubag->assignRole('pegawai');

        DB::table('saldo_cutis')->insert([
            'user_id' => $kasubag->id,
            'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0,
            'tahun_berjalan' => date('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $pejabat = User::create([
            'nip' => '196005051990121005',
            'nama' => 'Bapak Pejabat',
            'password' => Hash::make('196005051990121005'),
            'unit_kerja_id' => 1,
            'is_profile_completed' => true,
        ]);
        $pejabat->assignRole('pejabat_berwenang');
        $pejabat->assignRole('pegawai');

        DB::table('saldo_cutis')->insert([
            'user_id' => $pejabat->id,
            'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0,
            'tahun_berjalan' => date('Y'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
