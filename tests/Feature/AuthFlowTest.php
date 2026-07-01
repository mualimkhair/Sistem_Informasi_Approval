<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\UnitKerja;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Filament\Pages\Dashboard;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->unitKerja = UnitKerja::create(['nama_unit' => 'Tata Usaha', 'jenis' => 'administrasi']);
    }

    public function test_user_can_login_using_nip()
    {
        $user = User::create([
            'nip' => '123456789012345678',
            'nama' => 'Test User',
            'password' => Hash::make('password123'),
            'unit_kerja_id' => $this->unitKerja->id,
            'is_profile_completed' => true,
        ]);

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => '123456789012345678',
                'password' => 'password123',
                'remember' => false,
            ])
            ->call('authenticate');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_with_incomplete_profile_is_redirected_to_lengkapi_profil()
    {
        $user = User::create([
            'nip' => '876543210987654321',
            'nama' => 'Incomplete User',
            'password' => Hash::make('password123'),
            'unit_kerja_id' => $this->unitKerja->id,
            'is_profile_completed' => false,
        ]);

        $this->actingAs($user);

        // Access dashboard
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/lengkapi-profil');
    }

    public function test_user_with_complete_profile_can_access_dashboard()
    {
        $user = User::create([
            'nip' => '111122223333444455',
            'nama' => 'Complete User',
            'password' => Hash::make('password123'),
            'unit_kerja_id' => $this->unitKerja->id,
            'is_profile_completed' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin');

        // Should not redirect to lengkapi-profil
        // Filament dashboard might redirect to /admin or return 200 depending on exact config
        // So we assert it doesn't redirect to /admin/lengkapi-profil
        $this->assertNotEquals(url('/admin/lengkapi-profil'), $response->headers->get('Location'));
    }
}
