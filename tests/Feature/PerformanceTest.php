<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_homepage_loads_within_acceptable_time(): void
    {
        $start = microtime(true);
        
        $response = $this->get('/admin/login');
        
        $end = microtime(true);
        $executionTime = ($end - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(2000, $executionTime, 'Page load time exceeds 2 seconds');
    }

    public function test_database_seeding_performance(): void
    {
        $start = microtime(true);
        
        $this->artisan('db:seed', ['--force' => true]);
        
        $end = microtime(true);
        $executionTime = ($end - $start);

        $this->assertLessThan(30, $executionTime, 'Database seeding takes more than 30 seconds');
    }

    public function test_leave_calculation_performance(): void
    {
        $cutiService = new \App\Services\CutiService();
        $unitKerja = \App\Models\UnitKerja::where('jenis', 'administrasi')->first();

        $start = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $cutiService->hitungLamaCuti(
                \Carbon\Carbon::now()->addDays(7),
                \Carbon\Carbon::now()->addDays(14),
                $unitKerja,
                null
            );
        }
        
        $end = microtime(true);
        $executionTime = ($end - $start) * 1000;

        $this->assertLessThan(1000, $executionTime, '100 leave calculations take more than 1 second');
    }

    public function test_concurrent_user_sessions(): void
    {
        $users = \App\Models\User::limit(5)->get();
        
        foreach ($users as $user) {
            $response = $this->actingAs($user)->get('/admin');
            $response->assertStatus(200);
        }

        $this->assertTrue(true);
    }

    public function test_bulk_leave_request_creation(): void
    {
        $user = \App\Models\User::where('nip', '199202022020121002')->first();
        
        $start = microtime(true);
        
        for ($i = 0; $i < 50; $i++) {
            \App\Models\PengajuanCuti::create([
                'user_id' => $user->id,
                'jenis_cuti' => 'tahunan',
                'alasan_cuti' => "Test request {$i}",
                'tanggal_mulai' => \Carbon\Carbon::now()->addDays(7 + $i)->format('Y-m-d'),
                'tanggal_selesai' => \Carbon\Carbon::now()->addDays(9 + $i)->format('Y-m-d'),
                'alamat_selama_cuti' => 'Test Address',
            ]);
        }
        
        $end = microtime(true);
        $executionTime = ($end - $start);

        $this->assertLessThan(5, $executionTime, 'Creating 50 leave requests takes more than 5 seconds');
    }
}
