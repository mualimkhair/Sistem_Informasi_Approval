<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\PengajuanCuti;
use App\Filament\Resources\PengajuanCutis\Schemas\PengajuanCutiForm;

// Create dummy admin
$admin = User::firstOrCreate(['nip' => '11111111111111'], [
    'nama' => 'Admin Test',
    'password' => bcrypt('password'),
]);
$admin->assignRole('admin');

// Create dummy pegawai
$pegawai = User::firstOrCreate(['nip' => '22222222222222'], [
    'nama' => 'Pegawai Test',
    'password' => bcrypt('password'),
]);
$pegawai->assignRole('pegawai');

// Give pegawai some saldo
$pegawai->saldoCuti()->updateOrCreate(
    ['user_id' => $pegawai->id],
    ['saldo_n2' => 5, 'saldo_n1' => 6, 'saldo_n' => 7, 'tahun_berjalan' => now()->year]
);

// Give admin some saldo
$admin->saldoCuti()->updateOrCreate(
    ['user_id' => $admin->id],
    ['saldo_n2' => 10, 'saldo_n1' => 11, 'saldo_n' => 12, 'tahun_berjalan' => now()->year]
);

// Create cuti for pegawai
$cuti = PengajuanCuti::firstOrCreate([
    'user_id' => $pegawai->id,
    'jenis_cuti' => 'tahunan',
    'status' => 'menunggu_atasan',
    'alasan_cuti' => 'Test',
    'alamat_selama_cuti' => 'Test',
    'tanggal_mulai' => now()->addDays(2)->toDateString(),
    'tanggal_selesai' => now()->addDays(5)->toDateString(),
    'lama_cuti' => 3,
]);

auth()->login($admin);

// Simulate calling getSimulasiSaldo
$get = function($key) use ($cuti) {
    return $cuti->$key;
};

$saldo = PengajuanCutiForm::getSimulasiSaldo($get, $cuti);

echo "Admin name: {$admin->nama}\n";
echo "Pegawai name: {$pegawai->nama}\n";
echo "Simulasi Saldo for N2: {$saldo['n2']}\n"; // Should be 5 hari, not 10 hari
