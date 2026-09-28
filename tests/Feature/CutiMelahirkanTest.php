<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SaldoCuti;
use App\Models\PengajuanCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CutiMelahirkanTest extends TestCase
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

    public function test_laki_laki_tidak_dapat_saldo_melahirkan()
    {
        $user = $this->makeUser(['jenis_kelamin' => 'laki-laki']);

        $saldo = SaldoCuti::create([
            'user_id' => $user->id,
            'tahun_berjalan' => now()->year,
            'saldo_cuti_melahirkan' => 90,
        ]);

        $this->assertEquals(0, $saldo->fresh()->saldo_cuti_melahirkan);
    }

    public function test_perempuan_dapat_saldo_melahirkan()
    {
        $user = $this->makeUser(['jenis_kelamin' => 'perempuan']);

        $saldo = SaldoCuti::create([
            'user_id' => $user->id,
            'tahun_berjalan' => now()->year,
            'saldo_cuti_melahirkan' => 90,
        ]);

        $this->assertEquals(90, $saldo->fresh()->saldo_cuti_melahirkan);
    }

    public function test_saldo_melahirkan_menjadi_nol_saat_berubah_menjadi_laki_laki()
    {
        $user = $this->makeUser(['jenis_kelamin' => 'perempuan']);

        $saldo = SaldoCuti::create([
            'user_id' => $user->id,
            'tahun_berjalan' => now()->year,
            'saldo_cuti_melahirkan' => 90,
        ]);

        $user->update(['jenis_kelamin' => 'laki-laki']);

        $this->assertEquals(0, $saldo->fresh()->saldo_cuti_melahirkan);
    }

    public function test_laki_laki_tidak_dapat_mengajukan_cuti_melahirkan()
    {
        $user = $this->makeUser(['jenis_kelamin' => 'laki-laki']);

        $this->actingAsTab($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Cuti Melahirkan hanya tersedia untuk pegawai perempuan.');

        PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'melahirkan',
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addDays(2),
            'lama_cuti' => 3,
            'alasan_cuti' => 'test',
            'alamat_selama_cuti' => 'test',
        ]);
    }

    public function test_perempuan_dapat_mengajukan_cuti_melahirkan()
    {
        $user = $this->makeUser(['jenis_kelamin' => 'perempuan']);

        $this->actingAsTab($user);

        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'melahirkan',
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addDays(2),
            'lama_cuti' => 3,
            'alasan_cuti' => 'test',
            'alamat_selama_cuti' => 'test',
        ]);

        $this->assertNotNull($pengajuan->id);
    }

    public function test_user_non_admin_jenis_kelamin_null_diarahkan_ke_lengkapi_profil()
    {
        $user = $this->makeUser(['jenis_kelamin' => null]);

        $this->actingAsTab($user);

        $response = $this->get('/');
        $response->assertRedirect(route('filament.admin.pages.lengkapi-profil'));
    }

    public function test_user_admin_jenis_kelamin_null_tidak_diarahkan_ke_lengkapi_profil()
    {
        $user = User::create([
            'nama' => 'Admin User',
            'nip' => '123456789012345670',
            'password' => bcrypt('password'),
            'is_profile_completed' => true,
            'jenis_kelamin' => null,
        ]);
        $user->assignRole('admin');

        $this->actingAsTab($user);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_laki_laki_dapat_mengajukan_cuti_selain_melahirkan()
    {
        $user = $this->makeUser(['jenis_kelamin' => 'laki-laki']);

        $this->actingAsTab($user);

        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'tanggal_mulai' => now(),
            'tanggal_selesai' => now()->addDays(2),
            'lama_cuti' => 3,
            'alasan_cuti' => 'test tahunan',
            'alamat_selama_cuti' => 'test',
        ]);

        $this->assertNotNull($pengajuan->id);
    }
}

