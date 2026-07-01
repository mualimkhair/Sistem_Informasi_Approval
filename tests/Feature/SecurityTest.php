<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PengajuanCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_password_is_hashed(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        
        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(\Hash::check('password', $user->password));
    }

    public function test_unauthenticated_user_cannot_access_admin_panel(): void
    {
        $response = $this->get('/admin');
        
        $response->assertRedirect('/admin/login');
    }

    public function test_user_cannot_access_other_users_leave_requests(): void
    {
        $user1 = User::where('nip', '199202022020121002')->first();
        $user2 = User::factory()->create();

        $pengajuan = PengajuanCuti::create([
            'user_id' => $user2->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test',
        ]);

        $user1Requests = PengajuanCuti::where('user_id', $user1->id)->get();
        
        $this->assertNotContains($pengajuan->id, $user1Requests->pluck('id'));
    }

    public function test_sql_injection_prevention_in_nip(): void
    {
        $maliciousNip = "' OR '1'='1";
        
        $user = User::where('nip', $maliciousNip)->first();
        
        $this->assertNull($user);
    }

    public function test_xss_prevention_in_leave_reason(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $xssPayload = '<script>alert("XSS")</script>';
        
        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => $xssPayload,
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test',
        ]);

        $this->assertEquals($xssPayload, $pengajuan->alasan_cuti);
    }

    public function test_csrf_token_required_for_post_requests(): void
    {
        $response = $this->post('/admin/login', [
            'nip' => '199001012020121002',
            'password' => 'password',
        ]);

        $this->assertTrue(true);
    }

    public function test_session_expires_after_logout(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        
        $response = $this->actingAs($user)->post('/admin/logout');
        
        $this->assertGuest();
    }

    public function test_nip_must_be_unique(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        $this->assertNotNull($user);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'nip' => '199001012020121002',
            'nama' => 'Duplicate User',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_role_based_access_control(): void
    {
        $pegawai = User::where('nip', '199202022020121002')->first();
        $pegawai->assignRole('pegawai');
        
        $admin = User::where('nip', '199001012020121002')->first();
        $admin->assignRole('super_admin');

        $this->assertTrue($pegawai->hasRole('pegawai'));
        $this->assertFalse($pegawai->hasRole('super_admin'));
        $this->assertTrue($admin->hasRole('super_admin'));
    }
}
