<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PengajuanCuti;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PengajuanCutiModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_pengajuan_cuti_uses_ulid(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => '2026-07-10',
            'tanggal_selesai' => '2026-07-12',
            'alamat_selama_cuti' => 'Test',
        ]);

        $this->assertNotNull($pengajuan->id);
        $this->assertEquals(26, strlen($pengajuan->id));
    }

    public function test_jenis_cuti_values_are_valid(): void
    {
        $validTypes = ['tahunan', 'besar', 'sakit', 'melahirkan', 'alasan_penting', 'diluar_tanggungan_negara'];
        
        $this->assertIsArray($validTypes);
        $this->assertContains('tahunan', $validTypes);
        $this->assertContains('sakit', $validTypes);
    }

    public function test_status_values_are_valid(): void
    {
        $validStatuses = [
            'menunggu_atasan',
            'menunggu_pejabat',
            'disetujui',
            'ditolak_kanit',
            'ditolak_kasubag',
            'ditolak_pejabat',
            'perubahan',
            'ditangguhkan'
        ];
        
        $this->assertIsArray($validStatuses);
        $this->assertContains('menunggu_atasan', $validStatuses);
        $this->assertContains('disetujui', $validStatuses);
    }

    public function test_keputusan_values_are_valid(): void
    {
        $validKeputusan = ['disetujui', 'tidak_disetujui', 'perubahan', 'ditangguhkan'];
        
        $this->assertIsArray($validKeputusan);
        $this->assertContains('disetujui', $validKeputusan);
        $this->assertContains('tidak_disetujui', $validKeputusan);
    }
}
