<?php

namespace Tests\Feature;

use App\Models\PengajuanCuti;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Filament\Resources\PengajuanCutis\Pages\EditPengajuanCuti;

class PengajuanCutiFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'pegawai', 'kanit', 'kasubag', 'pejabat_berwenang', 'kanit_kepegawaian', 'kasubag_tu'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function createUser($role) {
        $user = User::create([
            'nama' => 'Test User ' . $role,
            'nip' => '12345678901234567' . rand(0,9),
            'password' => bcrypt('password')
        ]);
        $user->assignRole($role);
        return $user;
    }

    public function test_pegawai_edit_tanpa_ubah_tanggal_lulus()
    {
        $pegawai = $this->createUser('pegawai');
        $this->actingAsTab($pegawai);

        $workday = Carbon::today();
        while ($workday->isWeekend()) {
            $workday->subDay();
        }
        $workday = $workday->format('Y-m-d');

        $record = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => $workday,
            'tanggal_selesai' => $workday,
            'lama_cuti' => 1,
            'status' => 'perubahan',
            'alamat_selama_cuti' => 'Test',
            'nomor_telp' => '123'
        ]);

        $component = Livewire::test(EditPengajuanCuti::class, ['record' => $record->getKey()])
            ->fillForm([
                'tanggal_mulai' => $workday, // tidak diubah
                'alasan_cuti' => 'Alasan baru' // diubah
            ])
            ->call('save');

        $component->assertHasNoFormErrors(['tanggal_mulai']);
    }

    public function test_pegawai_edit_ubah_tanggal_ke_masa_lalu_ditolak()
    {
        $pegawai = $this->createUser('pegawai');
        $this->actingAsTab($pegawai);

        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');
        $pastWeek = Carbon::today()->subDays(7)->format('Y-m-d');

        $record = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => $yesterday,
            'tanggal_selesai' => $today,
            'lama_cuti' => 2,
            'status' => 'perubahan',
            'alamat_selama_cuti' => 'Test',
            'nomor_telp' => '123'
        ]);

        Livewire::test(EditPengajuanCuti::class, ['record' => $record->getKey()])
            ->fillForm([
                'tanggal_mulai' => $pastWeek, // diubah ke masa lalu yang beda
            ])
            ->call('save')
            ->assertHasFormErrors(['tanggal_mulai']); // Harus error
    }

    public function test_admin_koreksi_tanggal_ke_masa_lalu_lulus()
    {
        $admin = $this->createUser('admin');
        $pegawai = $this->createUser('pegawai');
        $this->actingAsTab($admin);

        $today = Carbon::today();
        while ($today->isWeekend()) {
            $today->addDay();
        }
        $pastWeek = $today->copy()->subWeek();

        $record = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => $today->format('Y-m-d'),
            'tanggal_selesai' => $today->format('Y-m-d'),
            'lama_cuti' => 1,
            'status' => 'disetujui',
            'alamat_selama_cuti' => 'Test',
            'nomor_telp' => '123'
        ]);

        Livewire::test(EditPengajuanCuti::class, ['record' => $record->getKey()])
            ->fillForm([
                'tanggal_mulai' => $pastWeek->format('Y-m-d'), // Admin memundurkan tanggal mulai
            ])
            ->call('save')
            ->assertHasNoFormErrors(['tanggal_mulai']);
    }

    public function test_kanit_edit_diri_sendiri_dianggap_pegawai()
    {
        $kanit = $this->createUser('kanit');
        $this->actingAsTab($kanit);

        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');
        $pastWeek = Carbon::today()->subDays(7)->format('Y-m-d');

        $record = PengajuanCuti::create([
            'user_id' => $kanit->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => $yesterday,
            'tanggal_selesai' => $today,
            'lama_cuti' => 2,
            'status' => 'perubahan',
            'alamat_selama_cuti' => 'Test',
            'nomor_telp' => '123'
        ]);

        Livewire::test(EditPengajuanCuti::class, ['record' => $record->getKey()])
            ->fillForm([
                'tanggal_mulai' => $pastWeek, // diubah ke masa lalu
            ])
            ->call('save')
            ->assertHasFormErrors(['tanggal_mulai']); // Harus error karena statusnya bukan admin, melainkan self-edit (edit_pegawai)
    }
}
