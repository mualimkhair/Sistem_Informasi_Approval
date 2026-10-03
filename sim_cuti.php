<?php
use App\Models\User;
use App\Models\PengajuanCuti;
use App\Models\UnitKerja;
use Illuminate\Support\Str;

$unit = UnitKerja::where('nama_unit', 'Unit Perencanaan dan Program')->first();
$pegawai = User::create([
    'nip' => 'SIM' . rand(1000,9999),
    'nama' => 'Pegawai Sim Perencanaan',
    'password' => bcrypt('password'),
    'unit_kerja_id' => $unit->id,
    'seksi_id' => $unit->seksi_id,
]);
$pegawai->assignRole('pegawai');
\DB::table('saldo_cutis')->insert(['user_id' => $pegawai->id, 'saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y'), 'created_at' => now(), 'updated_at' => now()]);

$pengajuan = PengajuanCuti::create([
    'user_id' => $pegawai->id,
    'jenis_cuti' => 'tahunan',
    'tanggal_mulai' => now()->addDays(2),
    'tanggal_selesai' => now()->addDays(3),
    'alasan_cuti' => 'Test',
    'alamat_selama_cuti' => 'Test',
]);

echo json_encode([
    'tipe_aliran' => $pengajuan->tipe_aliran,
    'status_awal' => $pengajuan->status,
    'keputusan_kanit' => $pengajuan->keputusan_kanit,
    'keputusan_kasubag' => $pengajuan->keputusan_kasubag,
    'kepala_unit_id' => $pengajuan->kepala_unit_id,
    'kanit_nama' => $pengajuan->kanit_nama,
]);
