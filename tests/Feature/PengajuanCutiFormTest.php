<?php

namespace Tests\Feature;

use App\Models\PengajuanCuti;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Filament\Resources\PengajuanCutis\Pages\EditPengajuanCuti;

class PengajuanCutiFormTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
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
        $this->actingAs($pegawai);

        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');

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

        $component = Livewire::test(EditPengajuanCuti::class, ['record' => $record->getKey()])
            ->fillForm([
                'tanggal_mulai' => $yesterday, // tidak diubah
                'alasan_cuti' => 'Alasan baru' // diubah
            ])
            ->call('save');

        if ($component->errors()->has('data.tanggal_mulai')) {
            dd($component->errors()->get('data.tanggal_mulai'));
        }

        $component->assertHasNoFormErrors(['tanggal_mulai']);
    }

    public function test_pegawai_edit_ubah_tanggal_ke_masa_lalu_ditolak()
    {
        $pegawai = $this->createUser('pegawai');
        $this->actingAs($pegawai);

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
        $this->actingAs($admin);

        $today = Carbon::today()->format('Y-m-d');
        $pastWeek = Carbon::today()->subDays(7)->format('Y-m-d');

        $record = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => $today,
            'tanggal_selesai' => $today,
            'lama_cuti' => 1,
            'status' => 'disetujui',
            'alamat_selama_cuti' => 'Test',
            'nomor_telp' => '123'
        ]);

        Livewire::test(EditPengajuanCuti::class, ['record' => $record->getKey()])
            ->fillForm([
                'tanggal_mulai' => $pastWeek, // Admin memundurkan tanggal mulai
            ])
            ->call('save')
            ->assertHasNoFormErrors(['tanggal_mulai']);
    }

    public function test_kanit_edit_diri_sendiri_dianggap_pegawai()
    {
        $kanit = $this->createUser('kanit');
        $this->actingAs($kanit);

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
