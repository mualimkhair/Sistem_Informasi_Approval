<?php

namespace Tests\Feature;

use App\Models\TabContext;
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
        $user = User::create(array_merge([
            'nama' => 'Test User',
            'nip' => '123456789012345678',
            'password' => bcrypt('password'),
            'is_profile_completed' => true,
        ], $extra));

        $user->assignRole('pegawai');

        return $user;
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

        $this->assertDatabaseCount('tab_contexts', 1);
        $context = TabContext::first();
        $this->assertEquals($user->id, $context->user_id);
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

    public function test_login_user_tanpa_role_panel_ditolak(): void
    {
        $user = User::create([
            'nama' => 'No Role User',
            'nip' => '111111111111111111',
            'password' => bcrypt('password'),
            'is_profile_completed' => true,
        ]);

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $user->nip,
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['nip']);

        $this->assertDatabaseCount('tab_contexts', 0);
    }

    public function test_rate_limit_login_dipisahkan_antar_nip(): void
    {
        $blockedUser = $this->makeUser([
            'nama' => 'Blocked User',
            'nip' => '222222222222222222',
        ]);
        $validUser = $this->makeUser([
            'nama' => 'Valid User',
            'nip' => '333333333333333333',
        ]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            Livewire::test(\App\Filament\Pages\Auth\Login::class)
                ->fillForm([
                    'nip' => $blockedUser->nip,
                    'password' => 'salah',
                    'remember' => false,
                ])
                ->call('authenticate')
                ->assertHasFormErrors(['nip']);
        }

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $blockedUser->nip,
                'password' => 'salah',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $validUser->nip,
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tab_contexts', ['user_id' => $validUser->id]);
    }

    public function test_profil_belum_lengkap_diarahkan_ke_lengkapi_profil(): void
    {
        $user = $this->makeUser(['is_profile_completed' => false]);

        $this->actingAsTab($user)
            ->get('/')
            ->assertRedirect(route('filament.admin.pages.lengkapi-profil'));
    }

    public function test_setelah_profil_lengkap_dashboard_dapat_diakses(): void
    {
        $user = $this->makeUser(['is_profile_completed' => true]);

        $this->actingAsTab($user)->get('/')->assertOk();
    }
}