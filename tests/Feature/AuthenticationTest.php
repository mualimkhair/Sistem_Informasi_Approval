<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'pegawai', 'kanit', 'kasubag', 'pejabat_berwenang', 'kanit_kepegawaian', 'kasubag_tu'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function makeUser(array $extra = []): User
    {
        return User::create(array_merge([
            'nama' => 'Test User',
            'nip' => '123456789012345678',
            'password' => bcrypt('password'),
            'is_profile_completed' => true,
        ], $extra));
    }

    public function test_guest_di_root_diarahkan_ke_halaman_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_guest_mengakses_resource_protected_diarahkan_ke_login(): void
    {
        $this->get('/pengajuan-cutis')->assertRedirect('/login');
    }

    public function test_login_valid_dengan_nip_dan_password(): void
    {
        $user = $this->makeUser();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $user->nip,
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticated();
    }

    public function test_login_password_salah_ditolak(): void
    {
        $user = $this->makeUser();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $user->nip,
                'password' => 'salah',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['nip']);

        $this->assertGuest();
    }

    public function test_login_nip_tidak_dikenal_ditolak(): void
    {
        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => '999999999999999999',
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['nip']);

        $this->assertGuest();
    }

    public function test_profil_belum_lengkap_diarahkan_ke_lengkapi_profil(): void
    {
        $user = $this->makeUser(['is_profile_completed' => false]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('filament.admin.pages.lengkapi-profil'));
    }

    public function test_setelah_profil_lengkap_dashboard_dapat_diakses(): void
    {
        $user = $this->makeUser(['is_profile_completed' => true]);

        $this->actingAs($user)->get('/')->assertOk();
    }
}