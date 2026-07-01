<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CutiService;
use App\Models\HariLibur;
use App\Models\UnitKerja;
use App\Models\KelompokKerja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CutiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $cutiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->cutiService = new CutiService();
    }

    public function test_calculate_leave_duration_excludes_weekends_for_administrative(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'administrasi')->first();

        $tanggalMulai = Carbon::parse('2026-07-07');
        $tanggalSelesai = Carbon::parse('2026-07-11');

        $lamaCuti = $this->cutiService->hitungLamaCuti(
            $tanggalMulai,
            $tanggalSelesai,
            $unitKerja,
            null
        );

        $this->assertEquals(5, $lamaCuti);
    }

    public function test_calculate_leave_duration_excludes_national_holidays(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'administrasi')->first();

        HariLibur::create([
            'tanggal' => '2026-07-08',
            'keterangan' => 'Test Holiday',
            'jenis' => 'libur_nasional',
        ]);

        $tanggalMulai = Carbon::parse('2026-07-07');
        $tanggalSelesai = Carbon::parse('2026-07-10');

        $lamaCuti = $this->cutiService->hitungLamaCuti(
            $tanggalMulai,
            $tanggalSelesai,
            $unitKerja,
            null
        );

        $this->assertEquals(3, $lamaCuti);
    }

    public function test_calculate_leave_duration_for_operational_with_custom_off_days(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'operasional')->first();
        
        $kelompokKerja = KelompokKerja::create([
            'unit_kerja_id' => $unitKerja->id,
            'nama_kelompok' => 'Shift A',
            'hari_libur_1' => 'Wednesday',
            'hari_libur_2' => 'Thursday',
        ]);

        $tanggalMulai = Carbon::parse('2026-07-06');
        $tanggalSelesai = Carbon::parse('2026-07-10');

        $lamaCuti = $this->cutiService->hitungLamaCuti(
            $tanggalMulai,
            $tanggalSelesai,
            $unitKerja,
            $kelompokKerja
        );

        $this->assertLessThan(5, $lamaCuti);
    }

    public function test_invalid_dates_returns_excluded_dates_with_reasons(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'administrasi')->first();

        HariLibur::create([
            'tanggal' => '2026-07-08',
            'keterangan' => 'Test Holiday',
            'jenis' => 'libur_nasional',
        ]);

        $tanggalMulai = Carbon::parse('2026-07-07');
        $tanggalSelesai = Carbon::parse('2026-07-11');

        $invalidDates = $this->cutiService->invalidDates(
            $tanggalMulai,
            $tanggalSelesai,
            $unitKerja,
            null
        );

        $this->assertIsArray($invalidDates);
        $this->assertNotEmpty($invalidDates);
    }

    public function test_single_day_leave_calculation(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'administrasi')->first();

        $tanggalMulai = Carbon::parse('2026-07-07');
        $tanggalSelesai = Carbon::parse('2026-07-07');

        $lamaCuti = $this->cutiService->hitungLamaCuti(
            $tanggalMulai,
            $tanggalSelesai,
            $unitKerja,
            null
        );

        $this->assertEquals(1, $lamaCuti);
    }

    public function test_leave_on_weekend_for_administrative_equals_zero(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'administrasi')->first();

        $tanggalMulai = Carbon::parse('2026-07-04');
        $tanggalSelesai = Carbon::parse('2026-07-05');

        $lamaCuti = $this->cutiService->hitungLamaCuti(
            $tanggalMulai,
            $tanggalSelesai,
            $unitKerja,
            null
        );

        $this->assertEquals(0, $lamaCuti);
    }

    public function test_leave_duration_calculation_accuracy(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'administrasi')->first();

        $tanggalMulai = Carbon::parse('2026-07-01');
        $tanggalSelesai = Carbon::parse('2026-07-31');

        $lamaCuti = $this->cutiService->hitungLamaCuti(
            $tanggalMulai,
            $tanggalSelesai,
            $unitKerja,
            null
        );

        $this->assertGreaterThan(0, $lamaCuti);
        $this->assertLessThanOrEqual(31, $lamaCuti);
    }

    public function test_collective_leave_is_excluded(): void
    {
        $unitKerja = UnitKerja::where('jenis', 'administrasi')->first();

        HariLibur::create([
            'tanggal' => '2026-07-09',
            'keterangan' => 'Cuti Bersama',
            'jenis' => 'cuti_bersama',
        ]);

        $tanggalMulai = Carbon::parse('2026-07-07');
        $tanggalSelesai = Carbon::parse('2026-07-10');

        $lamaCuti = $this->cutiService->hitungLamaCuti(
            $tanggalMulai,
            $tanggalSelesai,
            $unitKerja,
            null
        );

        $this->assertLessThanOrEqual(3, $lamaCuti);
    }
}
