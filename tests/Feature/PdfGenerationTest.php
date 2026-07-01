<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PengajuanCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_pdf_generation_route_exists(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'disetujui',
            'keputusan_kanit' => 'disetujui',
            'keputusan_kasubag' => 'disetujui',
            'keputusan_pejabat' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get("/pengajuan-cuti/{$pengajuan->id}/pdf");
        
        $response->assertStatus(200);
    }

    public function test_pdf_has_correct_content_type(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'disetujui',
        ]);

        $response = $this->actingAs($user)->get("/pengajuan-cuti/{$pengajuan->id}/pdf");
        
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_only_authenticated_user_can_generate_pdf(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'disetujui',
        ]);

        $response = $this->get("/pengajuan-cuti/{$pengajuan->id}/pdf");
        
        $response->assertRedirect('/admin/login');
    }
}
