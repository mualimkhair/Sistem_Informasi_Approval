<?php

$seksi = App\Models\Seksi::create(['nama_seksi' => 'Seksi Uji Coba Soft Delete']);

echo "\n--- [1] DATA BERHASIL DIBUAT ---\n";
echo "ID: {$seksi->id}\n";
echo "Nama Seksi: {$seksi->nama_seksi}\n";
echo "Status deleted_at: " . ($seksi->deleted_at ?: 'NULL (Masih Aktif)') . "\n";

// Lakukan Soft Delete
$seksi->delete();

$cek_soft = App\Models\Seksi::withTrashed()->find($seksi->id);
echo "\n--- [2] SETELAH DI-SOFT DELETE ---\n";
echo "Status deleted_at sekarang: " . $cek_soft->deleted_at . "\n";
$biasa = App\Models\Seksi::find($seksi->id);
echo "Jika dicari biasa (tanpa withTrashed): " . ($biasa ? 'Data Muncul' : 'Data Sembunyi / Tidak Muncul') . "\n";

// Lakukan Force Delete
$cek_soft->forceDelete();
$cek_force = App\Models\Seksi::withTrashed()->find($seksi->id);
echo "\n--- [3] SETELAH DI-FORCE DELETE (HAPUS PERMANEN) ---\n";
echo "Jika dicari walau dengan withTrashed: " . ($cek_force ? 'Data Masih Ada' : 'Data Benar-benar Hilang / Kosong') . "\n\n";

