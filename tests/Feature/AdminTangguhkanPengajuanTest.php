<?php

namespace Tests\Feature;

use App\Filament\Resources\PengajuanCutis\Pages\ListPengajuanCutis;
use App\Models\PengajuanCuti;
use App\Models\Seksi;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\CutiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTangguhkanPengajuanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $superAdmin;

    private User $kasubagTu;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'pegawai', 'kanit', 'kasubag', 'pejabat_berwenang', 'kanit_kepegawaian', 'kasubag_tu'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->admin = $this->makeUser('Admin', '100000000000000001');
        $this->admin->assignRole('admin');

        $this->superAdmin = $this->makeUser('Super Admin', '100000000000000002');
        $this->superAdmin->assignRole('super_admin');

        $kepalaUnit = $this->makeUser('KU', '100000000000000003');
        $kepalaUnit->assignRole('kanit');

        $kepalaSeksi = $this->makeUser('KS', '100000000000000004');
        $kepalaSeksi->assignRole('kasubag');

        $kanitKepegawaian = $this->makeUser('KanitKep', '100000000000000005');
        $kanitKepegawaian->assignRole('kanit_kepegawaian');

        $this->kasubagTu = $this->makeUser('KasubagTU', '100000000000000006');
        $this->kasubagTu->assignRole('kasubag_tu');

        $seksi = Seksi::create(['nama_seksi' => 'Seksi Operasional']);
        $seksi->update(['kepala_seksi_id' => $kepalaSeksi->id]);

        $unit = UnitKerja::create([
            'nama_unit' => 'Unit Operasional',
            'jenis' => 'operasional',
            'seksi_id' => $seksi->id,
            'kepala_unit_id' => $kepalaUnit->id,
        ]);

        $this->pegawai = $this->makeUser('Pegawai', '100000000000000007', [
            'unit_kerja_id' => $unit->id,
            'seksi_id' => $seksi->id,
        ]);
        $this->pegawai->assignRole('pegawai');
        $this->pegawai->saldoCuti()->create(['saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y')]);
    }

    private function makeUser(string $nama, string $nip, array $extra = []): User
    {
        return User::create(array_merge([
            'nama' => $nama,
            'nip' => $nip,
            'password' => bcrypt('password'),
        ], $extra));
    }

    private function approvedPengajuan(): PengajuanCuti
    {
        $pengajuan = PengajuanCuti::create([
            'user_id' => $this->pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'lama_cuti' => 3,
            'alamat_selama_cuti' => 'Rumah',
        ])->fresh();

        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan->update(['keputusan_kasubag_tu' => 'disetujui']);

        return $pengajuan->fresh();
    }

    public function test_admin_can_tangguhkan_disetujui_pengajuan(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->admin);

        CutiService::tangguhkanPengajuan($pengajuan);

        $this->assertEquals('ditangguhkan', $pengajuan->fresh()->status);
    }

    public function test_super_admin_can_tangguhkan_disetujui_pengajuan(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->superAdmin);

        CutiService::tangguhkanPengajuan($pengajuan);

        $this->assertEquals('ditangguhkan', $pengajuan->fresh()->status);
    }

    public function test_pegawai_cannot_tangguhkan_pengajuan(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->pegawai);

        $this->expectException(\RuntimeException::class);
        CutiService::tangguhkanPengajuan($pengajuan);
    }

    public function test_approver_cannot_tangguhkan_pengajuan(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->kasubagTu);

        $this->expectException(\RuntimeException::class);
        CutiService::tangguhkanPengajuan($pengajuan);
    }

    public function test_non_disetujui_record_cannot_be_tangguhkan(): void
    {
        $pengajuan = PengajuanCuti::create([
            'user_id' => $this->pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'lama_cuti' => 3,
            'alamat_selama_cuti' => 'Rumah',
        ])->fresh();

        $this->assertEquals('menunggu_kepala_unit', $pengajuan->status);
        $this->actingAs($this->admin);

        $this->expectException(\RuntimeException::class);
        CutiService::tangguhkanPengajuan($pengajuan);
    }

    public function test_status_menjadi_ditangguhkan(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->admin);

        CutiService::tangguhkanPengajuan($pengajuan);

        $this->assertEquals('ditangguhkan', $pengajuan->fresh()->status);
    }

    public function test_saldo_dikembalikan_sekali(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->assertEquals(9, $this->pegawai->fresh()->saldoCuti->saldo_n);
        $this->actingAs($this->admin);

        CutiService::tangguhkanPengajuan($pengajuan);

        $this->assertEquals(12, $this->pegawai->fresh()->saldoCuti->saldo_n);
        $release = DB::table('saldo_cuti_ledgers')
            ->where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'release')->count();
        $this->assertEquals(1, $release);
    }

    public function test_tidak_double_release(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->admin);

        CutiService::tangguhkanPengajuan($pengajuan);

        try {
            CutiService::tangguhkanPengajuan($pengajuan->fresh());
        } catch (\RuntimeException) {
        }

        $this->assertEquals('ditangguhkan', $pengajuan->fresh()->status);
        $this->assertEquals(12, $this->pegawai->fresh()->saldoCuti->saldo_n);
        $release = DB::table('saldo_cuti_ledgers')
            ->where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'release')->count();
        $this->assertEquals(1, $release);
    }

    public function test_status_log_disetujui_ke_ditangguhkan(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->admin);

        CutiService::tangguhkanPengajuan($pengajuan, 'Masa cuti tumpang tindih');

        $this->assertDatabaseHas('pengajuan_cuti_status_logs', [
            'pengajuan_cuti_id' => $pengajuan->id,
            'status_from' => 'disetujui',
            'status_to' => 'ditangguhkan',
            'changed_by' => $this->admin->id,
        ]);

        $log = DB::table('pengajuan_cuti_status_logs')
            ->where('pengajuan_cuti_id', $pengajuan->id)
            ->where('status_from', 'disetujui')
            ->where('status_to', 'ditangguhkan')
            ->first();
        $this->assertStringContainsString('Ditangguhkan oleh Admin', $log->keterangan);
        $this->assertStringContainsString('Masa cuti tumpang tindih', $log->keterangan);
    }

    public function test_notifikasi_dikirim_ke_pemohon(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->admin);

        CutiService::tangguhkanPengajuan($pengajuan);

        $data = DB::table('notifications')
            ->where('notifiable_id', $this->pegawai->id)
            ->pluck('data');
        $this->assertNotEmpty($data);
        $this->assertTrue(
            $data->contains(fn (string $row): bool => str_contains($row, 'DITANGGUHKAN'))
        );
    }

    public function test_riwayat_keputusan_terjaga(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->admin);

        CutiService::tangguhkanPengajuan($pengajuan);
        $pengajuan = $pengajuan->fresh();

        $this->assertEquals('disetujui', $pengajuan->keputusan_kepala_unit);
        $this->assertEquals('disetujui', $pengajuan->keputusan_kepala_seksi);
        $this->assertEquals('disetujui', $pengajuan->keputusan_kanit_kepegawaian);
        $this->assertEquals('disetujui', $pengajuan->keputusan_kasubag_tu);
    }

    public function test_pdf_tetap_valid_setelah_tangguh(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAs($this->admin);

        CutiService::tangguhkanPengajuan($pengajuan);
        $pengajuan = $pengajuan->fresh();

        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();

        $this->assertStringContainsString('PERSETUJUAN BERJENJANG', $html);
        $this->assertStringContainsString('KEPALA UNIT', $html);
        $this->assertStringContainsString('KASUBAG TU', $html);
        $this->assertStringContainsString('<u>KU</u>', $html);
        $this->assertStringContainsString('<u>KasubagTU</u>', $html);
    }

    public function test_admin_can_tangguhkan_administrasi_pengajuan(): void
    {
        $unitAdm = UnitKerja::create([
            'nama_unit' => 'Unit Administrasi',
            'jenis' => 'administrasi',
        ]);
        $pegawaiAdm = $this->makeUser('PegawaiAdm', '100000000000000008', ['unit_kerja_id' => $unitAdm->id]);
        $pegawaiAdm->assignRole('pegawai');
        $pegawaiAdm->saldoCuti()->create(['saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y')]);

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawaiAdm->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'lama_cuti' => 3,
            'alamat_selama_cuti' => 'Rumah',
            'status' => 'disetujui',
        ])->fresh();

        $this->assertEquals('administrasi', $pengajuan->tipe_aliran);
        $this->assertEquals(9, $pegawaiAdm->fresh()->saldoCuti->saldo_n);

        $this->actingAs($this->admin);
        CutiService::tangguhkanPengajuan($pengajuan);

        $this->assertEquals('ditangguhkan', $pengajuan->fresh()->status);
        $this->assertEquals(12, $pegawaiAdm->fresh()->saldoCuti->saldo_n);

        $notif = DB::table('notifications')->where('notifiable_id', $pegawaiAdm->id)->pluck('data');
        $this->assertTrue($notif->contains(fn (string $row): bool => str_contains($row, 'DITANGGUHKAN')));
    }

    public function test_admin_sees_tangguhkan_action_on_disetujui_record(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAsTab($this->admin);

        Livewire::test(ListPengajuanCutis::class)
            ->assertTableActionVisible('tangguhkan', $pengajuan);
    }

    public function test_pegawai_does_not_see_tangguhkan_action(): void
    {
        $pengajuan = $this->approvedPengajuan();
        $this->actingAsTab($this->pegawai);

        Livewire::test(ListPengajuanCutis::class)
            ->assertTableActionHidden('tangguhkan', $pengajuan);
    }
}
