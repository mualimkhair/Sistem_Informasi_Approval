<?php

namespace Tests\Feature;

use App\Filament\Widgets\MenungguKeputusanWidget;
use App\Models\PengajuanCuti;
use App\Models\Seksi;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\CutiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperasionalApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $kepalaUnit;

    private User $kepalaSeksi;

    private User $kanitKepegawaian;

    private User $kasubagTu;

    private User $pegawai;

    private UnitKerja $unit;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'pegawai', 'kanit', 'kasubag', 'pejabat_berwenang', 'kanit_kepegawaian', 'kasubag_tu'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $seksi = Seksi::create(['nama_seksi' => 'Seksi Operasional']);

        $this->kepalaUnit = $this->makeUser('KU', '100000000000000001');
        $this->kepalaUnit->assignRole('kanit');

        $this->kepalaSeksi = $this->makeUser('KS', '100000000000000002');
        $this->kepalaSeksi->assignRole('kasubag');

        $this->unit = UnitKerja::create([
            'nama_unit' => 'Unit Operasional',
            'jenis' => 'operasional',
            'seksi_id' => $seksi->id,
            'kepala_unit_id' => $this->kepalaUnit->id,
        ]);
        $seksi->update(['kepala_seksi_id' => $this->kepalaSeksi->id]);

        $this->kanitKepegawaian = $this->makeUser('KanitKep', '100000000000000003');
        $this->kanitKepegawaian->assignRole('kanit_kepegawaian');

        $this->kasubagTu = $this->makeUser('KasubagTU', '100000000000000004');
        $this->kasubagTu->assignRole('kasubag_tu');

        $this->pegawai = $this->makeUser('Pegawai', '100000000000000005', [
            'unit_kerja_id' => $this->unit->id,
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

    private function submit(array $overrides = []): PengajuanCuti
    {
        $attributes = array_merge([
            'user_id' => $this->pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'lama_cuti' => 3,
            'alamat_selama_cuti' => 'Rumah',
        ], $overrides);

        // Let the observer populate unit_kerja_id/seksi_id/tipe_aliran/snapshots/status
        $pengajuan = PengajuanCuti::create($attributes);
        $pengajuan->refresh();

        return $pengajuan;
    }

    public function test_operasional_submission_starts_at_stage_1_and_snapshots_approvers(): void
    {
        $pengajuan = $this->submit();

        $this->assertEquals('operasional', $pengajuan->tipe_aliran);
        $this->assertEquals('menunggu_kepala_unit', $pengajuan->status);
        $this->assertEquals($this->kepalaUnit->id, $pengajuan->kepala_unit_id);
        $this->assertEquals($this->kepalaSeksi->id, $pengajuan->kepala_seksi_id);
        $this->assertEquals($this->kanitKepegawaian->id, $pengajuan->kanit_kepegawaian_id);
        $this->assertEquals($this->kasubagTu->id, $pengajuan->kasubag_tu_id);
    }

    public function test_full_4_stage_approval_reaches_disetujui_and_potong_saldo(): void
    {
        $pengajuan = $this->submit();

        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui', 'alasan_kepala_unit' => null]);
        $this->assertEquals('menunggu_kepala_seksi', $pengajuan->fresh()->status);

        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui', 'alasan_kepala_seksi' => null]);
        $this->assertEquals('menunggu_kanit_kepegawaian', $pengajuan->fresh()->status);

        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui', 'alasan_kanit_kepegawaian' => null]);
        $this->assertEquals('menunggu_kasubag_tu', $pengajuan->fresh()->status);

        $pengajuan->update(['keputusan_kasubag_tu' => 'disetujui', 'alasan_kasubag_tu' => null]);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('disetujui', $pengajuan->status);

        $this->assertEquals(9, $this->pegawai->fresh()->saldoCuti->saldo_n);
        $this->assertDatabaseHas('saldo_cuti_ledgers', ['pengajuan_cuti_id' => $pengajuan->id, 'aksi' => 'potong']);

        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();
        $this->assertStringContainsString('PERSETUJUAN BERJENJANG', $html);
        $this->assertStringContainsString('KEPALA UNIT', $html);
        $this->assertStringContainsString('KASUBAG TU', $html);
    }

    public function test_stage_1_reject_goes_to_ditolak_kepala_unit(): void
    {
        $pengajuan = $this->submit();

        $pengajuan->update(['keputusan_kepala_unit' => 'tidak_disetujui', 'alasan_kepala_unit' => 'Tolak']);
        $this->assertEquals('ditolak_kepala_unit', $pengajuan->fresh()->status);
    }

    public function test_submitter_is_kepala_unit_skips_stage_1(): void
    {
        $this->pegawai = $this->kepalaUnit;
        $this->pegawai->unit_kerja_id = $this->unit->id;

        $pengajuan = $this->submit();
        $this->assertEquals('dilewati', $pengajuan->keputusan_kepala_unit);
        $this->assertEquals('menunggu_kepala_seksi', $pengajuan->fresh()->status);
    }

    public function test_administrasi_submission_keeps_legacy_flow(): void
    {
        $unitAdm = UnitKerja::create([
            'nama_unit' => 'Unit Administrasi',
            'jenis' => 'administrasi',
        ]);
        $this->pegawai->update(['unit_kerja_id' => $unitAdm->id]);

        $pengajuan = $this->submit();
        $pengajuan->refresh();
        $this->assertEquals('administrasi', $pengajuan->tipe_aliran);
        $this->assertEquals('menunggu_atasan', $pengajuan->status);
        $this->assertNull($pengajuan->kepala_unit_id);

        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();
        $this->assertStringContainsString('PERTIMBANGAN ATASAN LANGSUNG', $html);
        $this->assertStringNotContainsString('PERSETUJUAN BERJENJANG', $html);
    }

    public function test_resubmit_after_skipped_stage_1_keeps_skip_and_does_not_koreksi_saldo(): void
    {
        $this->pegawai = $this->kepalaUnit;
        $this->pegawai->unit_kerja_id = $this->unit->id;

        $pengajuan = $this->submit();
        $this->assertEquals('dilewati', $pengajuan->keputusan_kepala_unit);
        $this->assertEquals('menunggu_kepala_seksi', $pengajuan->status);

        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $this->assertEquals('menunggu_kanit_kepegawaian', $pengajuan->fresh()->status);

        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'perubahan', 'alasan_kanit_kepegawaian' => 'Ubah tanggal']);
        $this->assertEquals('perubahan', $pengajuan->fresh()->status);

        $countBefore = DB::table('notifications')->where('notifiable_id', $this->kepalaSeksi->id)->count();
        $this->assertEquals(1, $countBefore);

        $this->actingAs($this->kepalaUnit);
        $pengajuan->update([
            'tanggal_mulai' => '2026-10-06',
            'tanggal_selesai' => '2026-10-09',
        ]);
        $pengajuan = $pengajuan->fresh();

        // Stage 1 skip is preserved; first truly-open stage = Kepala Seksi
        $this->assertEquals('dilewati', $pengajuan->keputusan_kepala_unit);
        $this->assertEquals('menunggu_kepala_seksi', $pengajuan->status);
        $this->assertNull($pengajuan->keputusan_kepala_seksi);
        $this->assertEquals(4, $pengajuan->lama_cuti);

        // No koreksi ledger (Fix #1: resubmit must not double-adjust the hold)
        $this->assertDatabaseMissing('saldo_cuti_ledgers', ['pengajuan_cuti_id' => $pengajuan->id, 'aksi' => 'koreksi']);

        // Exactly one new notification to the next approver (no duplicate)
        $this->assertEquals(2, DB::table('notifications')->where('notifiable_id', $this->kepalaSeksi->id)->count());
    }

    public function test_stage_3_skipped_when_no_kanit_kepegawaian_holder(): void
    {
        $this->kanitKepegawaian->removeRole('kanit_kepegawaian');

        $pengajuan = $this->submit();
        $this->assertNull($pengajuan->kanit_kepegawaian_id);
        $this->assertEquals('dilewati', $pengajuan->keputusan_kanit_kepegawaian);
        $this->assertEquals('menunggu_kepala_unit', $pengajuan->status);

        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $this->assertEquals('menunggu_kepala_seksi', $pengajuan->fresh()->status);

        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        // Stage 3 skipped -> jumps straight to stage 4
        $this->assertEquals('menunggu_kasubag_tu', $pengajuan->status);

        $pengajuan->update(['keputusan_kasubag_tu' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('disetujui', $pengajuan->status);
        $this->assertEquals(9, $this->pegawai->fresh()->saldoCuti->saldo_n);
    }

    public function test_stage_4_dilewati_when_no_kasubag_tu_holder_finalizes_and_potongs_once(): void
    {
        $this->kasubagTu->removeRole('kasubag_tu');

        $pengajuan = $this->submit();
        // Stage 4 preset to dilewati BUT cannot finalize while stages 1-3 pending
        $this->assertEquals('dilewati', $pengajuan->keputusan_kasubag_tu);
        $this->assertEquals('menunggu_kepala_unit', $pengajuan->status);

        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);

        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('disetujui', $pengajuan->status);
        $this->assertEquals(9, $this->pegawai->fresh()->saldoCuti->saldo_n);
        $potong = DB::table('saldo_cuti_ledgers')
            ->where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'potong')->count();
        $this->assertEquals(1, $potong);
    }

    public function test_all_approvers_missing_auto_approves_once_and_no_hold(): void
    {
        $this->kanitKepegawaian->removeRole('kanit_kepegawaian');
        $this->kasubagTu->removeRole('kasubag_tu');

        $seksiTanpa = Seksi::create(['nama_seksi' => 'Seksi Tanpa Kepala']);
        $unitTanpa = UnitKerja::create([
            'nama_unit' => 'Unit Tanpa Kepala',
            'jenis' => 'operasional',
            'seksi_id' => $seksiTanpa->id,
        ]);
        $pegawai = $this->makeUser('Pegawai2', '100000000000000099', [
            'unit_kerja_id' => $unitTanpa->id,
            'seksi_id' => $seksiTanpa->id,
        ]);
        $pegawai->assignRole('pegawai');
        $pegawai->saldoCuti()->create(['saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y')]);

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'lama_cuti' => 3,
            'alamat_selama_cuti' => 'Rumah',
        ])->fresh();

        $this->assertEquals('disetujui', $pengajuan->status);
        $this->assertEquals(9, $pegawai->fresh()->saldoCuti->saldo_n);
        $potong = DB::table('saldo_cuti_ledgers')
            ->where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'potong')->count();
        $this->assertEquals(1, $potong);

        // holdSaldo must not create a hold for an already final submission
        CutiService::holdSaldo($pengajuan);
        $hold = DB::table('saldo_cuti_ledgers')
            ->where('pengajuan_cuti_id', $pengajuan->id)
            ->where('aksi', 'hold')->count();
        $this->assertEquals(0, $hold);
    }

    public function test_scope_kepala_unit_uses_snapshot_over_current_org_head(): void
    {
        $pengajuan = $this->submit(); // snapshots $this->kepalaUnit as KU

        $newKu = $this->makeUser('KUBaru', '100000000000000011');
        $newKu->assignRole('kanit');
        // Reassign org head quietly: UnitKerjaObserver re-syncs roles on update,
        // which would strip 'kanit' from the snapshotted KU and skew this test.
        $this->unit->updateQuietly(['kepala_unit_id' => $newKu->id]);

        $this->assertTrue(PengajuanCuti::forApprover($this->kepalaUnit)->whereKey($pengajuan->id)->exists());
        $this->assertFalse(PengajuanCuti::forApprover($newKu)->whereKey($pengajuan->id)->exists());
    }

    public function test_scope_stage_3_restricted_to_snapshotted_kanit_kepegawaian(): void
    {
        $pengajuan = $this->submit();
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('menunggu_kanit_kepegawaian', $pengajuan->status);

        $secondHolder = $this->makeUser('KanitKep2', '100000000000000012');
        $secondHolder->assignRole('kanit_kepegawaian');

        $this->assertTrue(PengajuanCuti::forApprover($this->kanitKepegawaian)->whereKey($pengajuan->id)->exists());
        $this->assertFalse(PengajuanCuti::forApprover($secondHolder)->whereKey($pengajuan->id)->exists());
    }

    public function test_pdf_operasional_ku_and_ks_render_without_signature(): void
    {
        Storage::fake('public');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        foreach ([$this->kepalaUnit, $this->kepalaSeksi, $this->kanitKepegawaian, $this->kasubagTu] as $i => $user) {
            $path = 'sig/'.$i.'.png';
            Storage::disk('public')->put($path, $png);
            $user->update(['signature_path' => $path]);
        }

        $pengajuan = $this->submit();
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan->update(['keputusan_kasubag_tu' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();

        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();

        // KU & KS approve without signing; only Kanit Kepegawaian + Kasubag TU render signatures
        $this->assertEquals(2, substr_count($html, 'class="signature-img"'));
        $this->assertStringContainsString('<u>KU</u>', $html);
        $this->assertStringContainsString('<u>KS</u>', $html);
        $this->assertStringContainsString('<u>KanitKep</u>', $html);
        $this->assertStringContainsString('<u>KasubagTU</u>', $html);
    }

    public function test_widget_merges_stats_for_dual_role_user(): void
    {
        $this->kepalaUnit->assignRole('kanit_kepegawaian');
        $this->actingAs($this->kepalaUnit);

        $widget = new class extends MenungguKeputusanWidget
        {
            public function getStatsPublic(): array
            {
                return $this->getStats();
            }
        };

        $stats = $widget->getStatsPublic();
        $this->assertGreaterThanOrEqual(2, count($stats));
    }
}
