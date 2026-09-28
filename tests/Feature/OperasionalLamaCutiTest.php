<?php

namespace Tests\Feature;

use App\Models\KelompokKerja;
use App\Models\Seksi;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\CutiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperasionalLamaCutiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $nama, string $nip): User
    {
        return User::create([
            'nama' => $nama,
            'nip' => $nip,
            'email' => strtolower(str_replace(' ', '', $nama)).'@example.com',
            'password' => bcrypt('password'),
            'jenis_kelamin' => 'laki-laki',
        ]);
    }

    public function test_non_operasional_mengikuti_aturan_sabtu_minggu(): void
    {
        $seksi = Seksi::create(['nama_seksi' => 'Seksi IT']);
        $unit = UnitKerja::create([
            'nama_unit' => 'Unit IT',
            'jenis' => 'non-operasional',
            'seksi_id' => $seksi->id,
        ]);

        $pegawai = $this->makeUser('Pegawai IT', '100000000000000001');
        $pegawai->update([
            'unit_kerja_id' => $unit->id,
            'seksi_id' => $seksi->id,
        ]);

        // Cuti dari Senin, 5 Okt 2026 s/d Minggu, 11 Okt 2026
        // Hari libur Sabtu (10 Okt) dan Minggu (11 Okt)
        // Lama cuti harusnya 5 hari (Sen, Sel, Rab, Kam, Jum)
        $start = Carbon::parse('2026-10-05');
        $end = Carbon::parse('2026-10-11');

        $lamaCuti = CutiService::hitungLamaCuti($start, $end, $pegawai->unitKerja, null);

        $this->assertEquals(5, $lamaCuti);
    }

    public function test_operasional_dengan_libur_selasa_rabu_menghitung_senin_sampai_kamis_sebagai_2_hari(): void
    {
        $seksi = Seksi::create(['nama_seksi' => 'Seksi Operasional']);
        $unit = UnitKerja::create([
            'nama_unit' => 'Unit Operasional',
            'jenis' => 'operasional',
            'seksi_id' => $seksi->id,
        ]);

        $kelompok = KelompokKerja::create([
            'unit_kerja_id' => $unit->id,
            'nama_kelompok' => 'Shift 1',
            'hari_libur_1' => 'Selasa',
            'hari_libur_2' => 'Rabu',
        ]);

        $pegawai = $this->makeUser('Pegawai Ops 1', '100000000000000002');
        $pegawai->update([
            'unit_kerja_id' => $unit->id,
            'seksi_id' => $seksi->id,
        ]);

        // Cuti dari Senin, 5 Okt 2026 s/d Kamis, 8 Okt 2026
        // Libur: Selasa (6 Okt), Rabu (7 Okt)
        // Lama cuti harusnya 2 hari (Senin, Kamis)
        $start = Carbon::parse('2026-10-05');
        $end = Carbon::parse('2026-10-08');

        $lamaCuti = CutiService::hitungLamaCuti($start, $end, $pegawai->unitKerja, $kelompok);

        $this->assertEquals(2, $lamaCuti);
    }

    public function test_sabtu_minggu_tetap_dihitung_sebagai_hari_cuti_operasional_jika_bukan_hari_libur_kelompok(): void
    {
        $seksi = Seksi::create(['nama_seksi' => 'Seksi Operasional']);
        $unit = UnitKerja::create([
            'nama_unit' => 'Unit Operasional',
            'jenis' => 'operasional',
            'seksi_id' => $seksi->id,
        ]);

        $kelompok = KelompokKerja::create([
            'unit_kerja_id' => $unit->id,
            'nama_kelompok' => 'Shift 1',
            'hari_libur_1' => 'Selasa',
            'hari_libur_2' => 'Rabu',
        ]);

        $pegawai = $this->makeUser('Pegawai Ops 1', '100000000000000002');
        $pegawai->update([
            'unit_kerja_id' => $unit->id,
            'seksi_id' => $seksi->id,
        ]);

        // Cuti dari Jumat, 9 Okt 2026 s/d Senin, 12 Okt 2026
        // Sabtu (10 Okt) dan Minggu (11 Okt) bukan hari libur Shift 1
        // Lama cuti harusnya 4 hari (Jum, Sab, Min, Sen)
        $start = Carbon::parse('2026-10-09');
        $end = Carbon::parse('2026-10-12');

        $lamaCuti = CutiService::hitungLamaCuti($start, $end, $pegawai->unitKerja, $kelompok);

        $this->assertEquals(4, $lamaCuti);
    }

    public function test_kelompok_kerja_berbeda_menghasilkan_perhitungan_sesuai_hari_liburnya(): void
    {
        $seksi = Seksi::create(['nama_seksi' => 'Seksi Operasional']);
        $unit = UnitKerja::create([
            'nama_unit' => 'Unit Operasional',
            'jenis' => 'operasional',
            'seksi_id' => $seksi->id,
        ]);

        $kelompokA = KelompokKerja::create([
            'unit_kerja_id' => $unit->id,
            'nama_kelompok' => 'Shift A',
            'hari_libur_1' => 'Senin',
            'hari_libur_2' => 'Kamis',
        ]);

        $kelompokB = KelompokKerja::create([
            'unit_kerja_id' => $unit->id,
            'nama_kelompok' => 'Shift B',
            'hari_libur_1' => 'Selasa',
            'hari_libur_2' => 'Jumat',
        ]);

        $start = Carbon::parse('2026-10-05'); // Senin
        $end = Carbon::parse('2026-10-09');   // Jumat

        // Shift A: Libur Senin & Kamis.
        // Cuti (Sen, Sel, Rab, Kam, Jum). Libur Senin & Kamis. Jadi 3 hari cuti.
        $lamaA = CutiService::hitungLamaCuti($start, $end, clone $unit, $kelompokA);
        $this->assertEquals(3, $lamaA);

        // Shift B: Libur Selasa & Jumat.
        // Cuti (Sen, Sel, Rab, Kam, Jum). Libur Selasa & Jumat. Jadi 3 hari cuti.
        $lamaB = CutiService::hitungLamaCuti($start, $end, clone $unit, $kelompokB);
        $this->assertEquals(3, $lamaB);
    }
}
