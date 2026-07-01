<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UnitKerja;
use App\Models\KelompokKerja;
use App\Models\HariLibur;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_unit_kerja_can_be_created(): void
    {
        $unitKerja = UnitKerja::create([
            'nama_unit' => 'Unit Test',
            'jenis' => 'administrasi',
        ]);

        $this->assertDatabaseHas('unit_kerjas', [
            'nama_unit' => 'Unit Test',
            'jenis' => 'administrasi',
        ]);
    }

    public function test_unit_kerja_has_two_types(): void
    {
        $administrasi = UnitKerja::where('jenis', 'administrasi')->first();
        $operasional = UnitKerja::where('jenis', 'operasional')->first();

        $this->assertNotNull($administrasi);
        $this->assertNotNull($operasional);
    }

    public function test_kelompok_kerja_belongs_to_unit_kerja(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'operasional')->first();
        
        $kelompokKerja = KelompokKerja::create([
            'unit_kerja_id' => $unitKerja->id,
            'nama_kelompok' => 'Shift Test',
            'hari_libur_1' => 'Wednesday',
            'hari_libur_2' => 'Thursday',
        ]);

        $this->assertEquals($unitKerja->id, $kelompokKerja->unit_kerja_id);
        $this->assertNotNull($kelompokKerja->unitKerja);
    }

    public function test_kelompok_kerja_has_custom_off_days(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'operasional')->first();
        
        $kelompokKerja = KelompokKerja::create([
            'unit_kerja_id' => $unitKerja->id,
            'nama_kelompok' => 'Shift A',
            'hari_libur_1' => 'Monday',
            'hari_libur_2' => 'Tuesday',
        ]);

        $this->assertEquals('Monday', $kelompokKerja->hari_libur_1);
        $this->assertEquals('Tuesday', $kelompokKerja->hari_libur_2);
    }

    public function test_hari_libur_can_be_created(): void
    {
        $hariLibur = HariLibur::create([
            'tanggal' => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI',
            'jenis' => 'libur_nasional',
        ]);

        $this->assertDatabaseHas('hari_liburs', [
            'tanggal' => '2026-08-17',
            'jenis' => 'libur_nasional',
        ]);
    }

    public function test_hari_libur_has_two_types(): void
    {
        HariLibur::create([
            'tanggal' => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI',
            'jenis' => 'libur_nasional',
        ]);

        HariLibur::create([
            'tanggal' => '2026-12-24',
            'keterangan' => 'Cuti Bersama Natal',
            'jenis' => 'cuti_bersama',
        ]);

        $this->assertDatabaseHas('hari_liburs', ['jenis' => 'libur_nasional']);
        $this->assertDatabaseHas('hari_liburs', ['jenis' => 'cuti_bersama']);
    }

    public function test_hari_libur_tanggal_is_unique(): void
    {
        HariLibur::create([
            'tanggal' => '2026-08-17',
            'keterangan' => 'Hari Kemerdekaan RI',
            'jenis' => 'libur_nasional',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        HariLibur::create([
            'tanggal' => '2026-08-17',
            'keterangan' => 'Duplicate',
            'jenis' => 'libur_nasional',
        ]);
    }

    public function test_unit_kerja_administrasi_count(): void
    {
        $count = UnitKerja::where('jenis', 'administrasi')->count();
        $this->assertGreaterThan(0, $count);
    }

    public function test_unit_kerja_operasional_count(): void
    {
        $count = UnitKerja::where('jenis', 'operasional')->count();
        $this->assertGreaterThan(0, $count);
    }
}
