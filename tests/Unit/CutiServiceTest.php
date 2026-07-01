<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\HariLibur;
use App\Models\KelompokKerja;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\SaldoCuti;
use App\Models\PengajuanCuti;
use App\Services\CutiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class CutiServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_hitung_lama_cuti_normal_excludes_weekends()
    {
        $start = Carbon::parse('2026-06-01'); // Senin
        $end = Carbon::parse('2026-06-07');   // Minggu

        $unitKerja = UnitKerja::create(['nama_unit' => 'Tata Usaha', 'jenis' => 'administrasi']);

        $days = CutiService::hitungLamaCuti($start, $end, $unitKerja, null);

        $this->assertEquals(5, $days);
    }

    public function test_hitung_lama_cuti_excludes_national_holidays()
    {
        $start = Carbon::parse('2026-06-01');
        $end = Carbon::parse('2026-06-05');

        HariLibur::create([
            'tanggal' => '2026-06-03',
            'keterangan' => 'Hari Libur Nasional',
            'jenis' => 'libur_nasional'
        ]);

        $unitKerja = UnitKerja::create(['nama_unit' => 'Tata Usaha', 'jenis' => 'administrasi']);

        $days = CutiService::hitungLamaCuti($start, $end, $unitKerja, null);

        $this->assertEquals(4, $days);
    }

    public function test_hitung_lama_cuti_operasional_excludes_custom_holidays_instead_of_weekends()
    {
        $start = Carbon::parse('2026-06-01');
        $end = Carbon::parse('2026-06-07');

        $unitKerja = UnitKerja::create(['nama_unit' => 'Avsec', 'jenis' => 'operasional']);
        $kelompok = KelompokKerja::create([
            'unit_kerja_id' => $unitKerja->id,
            'nama_kelompok' => 'Regu A',
            'hari_libur_1' => 'Selasa',
            'hari_libur_2' => 'Rabu'
        ]);

        $days = CutiService::hitungLamaCuti($start, $end, $unitKerja, $kelompok);

        $this->assertEquals(5, $days);
    }

    public function test_potong_saldo_cuti_tahunan()
    {
        \Spatie\Permission\Models\Role::create(['name' => 'kanit']);
        \Spatie\Permission\Models\Role::create(['name' => 'kasubag']);

        $unitKerja = UnitKerja::create(['nama_unit' => 'Tata Usaha', 'jenis' => 'administrasi']);
        $user = User::create([
            'nip' => '123456789012345678',
            'nama' => 'Test User',
            'password' => Hash::make('password'),
            'unit_kerja_id' => $unitKerja->id
        ]);
        
        $saldo = SaldoCuti::create([
            'user_id' => $user->id,
            'saldo_n' => 12,
            'saldo_n1' => 2,
            'saldo_n2' => 1,
            'tahun_berjalan' => date('Y')
        ]);

        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'lama_cuti' => 4,
            'alamat_selama_cuti' => 'Di rumah',
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addDays(4),
            'status' => 'menunggu_pejabat',
            'keputusan_pejabat' => 'disetujui'
        ]);

        CutiService::potongSaldo($pengajuan);

        $saldo->refresh();

        $this->assertEquals(0, $saldo->saldo_n2);
        $this->assertEquals(0, $saldo->saldo_n1);
        $this->assertEquals(11, $saldo->saldo_n);
    }
}
