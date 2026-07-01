<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_login_page_can_be_accessed(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        
        $response = $this->post('/admin/login', [
            'nip' => '199001012020121002',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post('/admin/login', [
            'nip' => '199001012020121002',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        
        $response = $this->actingAs($user)->post('/admin/logout');
        
        $this->assertGuest();
    }

    public function test_profile_completion_required_for_new_users(): void
    {
        $user = User::factory()->create([
            'is_profile_completed' => false,
            'alamat' => null,
            'nomor_telp' => null,
        ]);

        $response = $this->actingAs($user)->get('/admin');
        
        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_admin_panel(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        $user->assignRole('super_admin');
        
        $response = $this->actingAs($user)->get('/admin');
        
        $response->assertStatus(200);
    }

    public function test_pegawai_can_access_admin_panel(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        $user->assignRole('pegawai');
        
        $response = $this->actingAs($user)->get('/admin');
        
        $response->assertStatus(200);
    }
}
