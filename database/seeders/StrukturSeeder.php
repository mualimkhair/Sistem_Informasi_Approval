<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Seksi;
use App\Models\UnitKerja;

class StrukturSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Delete order: children to parent
        DB::table('saldo_cuti_ledgers')->delete();
        DB::table('pengajuan_cutis')->delete();
        DB::table('saldo_cutis')->delete();
        DB::table('kelompok_kerjas')->delete();
        // Since unit_kerjas and seksis reference users (and users reference them), we can nullify FKs or just delete them directly if no restrict.
        // But to be safe, nullify FKs first
        DB::table('users')->update(['unit_kerja_id' => null, 'seksi_id' => null]);
        DB::table('unit_kerjas')->update(['kepala_unit_id' => null, 'seksi_id' => null]);
        DB::table('seksis')->update(['kepala_seksi_id' => null]);

        DB::table('unit_kerjas')->delete();
        DB::table('seksis')->delete();
        
        // Remove users (except roles)
        $rolesId = DB::table('model_has_roles')->pluck('model_id')->toArray();
        DB::table('model_has_roles')->delete();
        DB::table('users')->delete();

        // 2. Seed Super Admin (NIP 18 nol)
        $superAdmin = User::create([
            'nip' => '000000000000000000',
            'nama' => 'Super Admin',
            'password' => Hash::make('000000000000000000'),
            'is_profile_completed' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        // 3. Seed Kabandara (Pejabat Berwenang)
        $kabandara = User::create([
            'nip' => '197804042002121003',
            'nama' => 'Prasetiyohadi, S.T, S.H, M.H',
            'password' => Hash::make('197804042002121003'),
            'pangkat_gol' => 'Pembina Tk.I (IV/b)',
            'is_profile_completed' => false,
        ]);
        $kabandara->assignRole('pejabat_berwenang');
        $kabandara->assignRole('pegawai');
        DB::table('saldo_cutis')->insert(['user_id' => $kabandara->id, 'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y'), 'created_at' => now(), 'updated_at' => now()]);

        // 4. Struktur Seksi & Unit
        $seksisData = [
            'Kepala Seksi Kampen dan Pelayanan Darurat' => [
                'kasi' => ['nama' => 'Rasud Mohamad, SH', 'nip' => '197109121992031003', 'gol' => 'Penata Tk.I (III/d)'],
                'units' => [
                    ['nama_unit' => 'Unit Kargo', 'kanit' => ['nama' => 'Adhitya Syah Putra', 'nip' => '199109042009121001', 'gol' => 'Penata Muda (III/a)']],
                    ['nama_unit' => 'Unit Terminal dan Pengamanan Kampen', 'kanit' => ['nama' => 'Fery Sefrian, S.M', 'nip' => '199009192014021004', 'gol' => 'Penata Muda Tk.I (III/b)']],
                    ['nama_unit' => 'Unit Proteksi', 'kanit' => ['nama' => 'Moh. Rifan', 'nip' => '198404112009011009', 'gol' => 'Penata Muda (III/a)']],
                    ['nama_unit' => 'Unit PKP-PK', 'kanit' => ['nama' => 'Muhammad Nur, S.Sos', 'nip' => '198302072006041004', 'gol' => 'Penata Tk.I (III/d)']],
                ]
            ],
            'Kepala Seksi Pelayanan dan Kerjasama' => [
                'kasi' => ['nama' => 'Muhammad Arief Sagana, SE, MM', 'nip' => '198512222007121001', 'gol' => 'Penata (III/c)'],
                'units' => [
                    ['nama_unit' => 'Unit Kerjasama', 'kanit' => ['nama' => 'Haryati Mihari, SE', 'nip' => '198109122009122003', 'gol' => 'Penata (III/c)']],
                    ['nama_unit' => 'Unit Informasi', 'kanit' => ['nama' => 'Nurasma, SE', 'nip' => '197410252006042001', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit Terminal, Hygiene dan Sanitasi', 'kanit' => ['nama' => 'Romi Yosep Sigar', 'nip' => '198610132010121002', 'gol' => 'Pengatur Tk.I (II/d)']],
                ]
            ],
            'Kepala Seksi Teknik dan Operasi' => [
                'kasi' => ['nama' => 'Winariyanto, SE', 'nip' => '197704271999031004', 'gol' => 'Penata Tk.I (III/d)'],
                'units' => [
                    ['nama_unit' => 'Unit Fasilitas Elektronika', 'kanit' => ['nama' => 'A S I S, A.Ma', 'nip' => '196911252002121001', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit AMC', 'kanit' => ['nama' => 'Hendra, SE, MM', 'nip' => '198205162006041001', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit Elektrikal Mekanikal', 'kanit' => ['nama' => 'Mochammad Dhamar Tri Saputro, A.Md.T', 'nip' => '198912282014021004', 'gol' => 'Penata Muda Tk.I (III/b)']],
                    ['nama_unit' => 'Unit Bangunan', 'kanit' => ['nama' => 'Subhan, ST', 'nip' => '197806102002121003', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit Alat-Alat Besar (A2B)', 'kanit' => ['nama' => 'Andi Reza Asyari Iqbal, A.Md', 'nip' => '198706072014021004', 'gol' => 'Penata Muda Tk.I (III/b)']],
                    ['nama_unit' => 'Unit Landasan', 'kanit' => ['nama' => 'Yunus Panto, SH', 'nip' => '198012142007121001', 'gol' => 'Penata Muda Tk.I (III/b)']],
                ]
            ],
            'Kasubag Keuangan dan Tata Usaha' => [
                'kasi' => ['nama' => 'Hastuty, SE, MM', 'nip' => '197504211999032001', 'gol' => 'Pembina (IV/a)'],
                'units' => [
                    ['nama_unit' => 'Unit Perencanaan dan Program', 'kanit' => ['nama' => 'Musdalifah, ST, MT', 'nip' => '198209222002122001', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit Kepegawaian', 'kanit' => ['nama' => 'Asmaul Husna Sabil, SE', 'nip' => '198202092006042001', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit Teknologi dan Humas', 'kanit' => ['nama' => 'Hermawan Susilo, S.Kom', 'nip' => '198210162006041001', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit Keuangan', 'kanit' => ['nama' => 'Muhajir', 'nip' => '197204211997031003', 'gol' => 'Penata Muda Tk.I (III/b)']],
                    ['nama_unit' => 'Unit PPID', 'kanit' => ['nama' => 'Ni\'ma, S.A.P', 'nip' => '197810062002122001', 'gol' => 'Penata (III/c)']],
                    ['nama_unit' => 'Unit Pengevaluasi dan Penyusunan Laporan', 'kanit' => ['nama' => 'Supriyadi, SE', 'nip' => '197804042006041002', 'gol' => 'Penata (III/c)']],
                    ['nama_unit' => 'Unit SPI', 'kanit' => ['nama' => 'Umar, S.Kom', 'nip' => '197706112006041001', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit BMN', 'kanit' => ['nama' => 'Yani Yuliawati, S.Sos, M.M', 'nip' => '197607232006042002', 'gol' => 'Penata Tk.I (III/d)']],
                    ['nama_unit' => 'Unit Tata Usaha', 'kanit' => null],
                ]
            ],
        ];

        foreach ($seksisData as $namaSeksi => $data) {
            $seksi = Seksi::create(['nama_seksi' => $namaSeksi]);
            
            $kasiUser = User::create([
                'nip' => $data['kasi']['nip'],
                'nama' => $data['kasi']['nama'],
                'password' => Hash::make($data['kasi']['nip']),
                'pangkat_gol' => $data['kasi']['gol'],
                'is_profile_completed' => false,
                'seksi_id' => $seksi->id,
            ]);
            $kasiUser->assignRole('kasubag');
            $kasiUser->assignRole('pegawai');
            DB::table('saldo_cutis')->insert(['user_id' => $kasiUser->id, 'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y'), 'created_at' => now(), 'updated_at' => now()]);

            $seksi->update(['kepala_seksi_id' => $kasiUser->id]);

            foreach ($data['units'] as $unitData) {
                $jenis = ($namaSeksi === 'Kasubag Keuangan dan Tata Usaha') ? 'administrasi' : 'operasional';
                $unit = UnitKerja::create([
                    'nama_unit' => $unitData['nama_unit'],
                    'jenis' => $jenis,
                    'seksi_id' => $seksi->id,
                ]);

                if ($unitData['kanit']) {
                    $kanitUser = User::create([
                        'nip' => $unitData['kanit']['nip'],
                        'nama' => $unitData['kanit']['nama'],
                        'password' => Hash::make($unitData['kanit']['nip']),
                        'pangkat_gol' => $unitData['kanit']['gol'],
                        'is_profile_completed' => false,
                        'unit_kerja_id' => $unit->id,
                        'seksi_id' => $seksi->id,
                    ]);
                    $kanitUser->assignRole('kanit');
                    $kanitUser->assignRole('pegawai');
                    DB::table('saldo_cutis')->insert(['user_id' => $kanitUser->id, 'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y'), 'created_at' => now(), 'updated_at' => now()]);

                    $unit->update(['kepala_unit_id' => $kanitUser->id]);
                }
            }
        }
    }
}
