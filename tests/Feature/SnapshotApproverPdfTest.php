<?php

namespace Tests\Feature;

use App\Models\PengajuanCuti;
use App\Models\Seksi;
use App\Models\UnitKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SnapshotApproverPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $kepalaUnit;

    private User $kepalaSeksi;

    private User $kanitKepegawaian;

    private User $kasubagTu;

    private User $pegawai;

    private User $kanitAdm;

    private User $kasubagAdm;

    private User $pejabat;

    private User $pegawaiAdm;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'pegawai', 'kanit', 'kasubag', 'pejabat_berwenang', 'kanit_kepegawaian', 'kasubag_tu'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->setUpOperasional();
        $this->setUpAdministrasi();
    }

    private function makeUser(string $nama, string $nip, array $extra = []): User
    {
        return User::create(array_merge([
            'nama' => $nama,
            'nip' => $nip,
            'password' => bcrypt('password'),
        ], $extra));
    }

    private function setUpOperasional(): void
    {
        $seksi = Seksi::create(['nama_seksi' => 'Seksi Operasional']);
        $this->kepalaUnit = $this->makeUser('KU', '100000000000000001');
        $this->kepalaUnit->assignRole('kanit');
        $this->kepalaSeksi = $this->makeUser('KS', '100000000000000002');
        $this->kepalaSeksi->assignRole('kasubag');

        $unit = UnitKerja::create([
            'nama_unit' => 'Unit Operasional',
            'jenis' => 'operasional',
            'seksi_id' => $seksi->id,
            'kepala_unit_id' => $this->kepalaUnit->id,
        ]);
        $seksi->update(['kepala_seksi_id' => $this->kepalaSeksi->id]);

        $this->kanitKepegawaian = $this->makeUser('KanitKep', '100000000000000003', [
            'pangkat_gol' => 'Penata Muda (III/a)',
            'jabatan' => 'Kepala Bagian Kepegawaian',
        ]);
        $this->kanitKepegawaian->assignRole('kanit_kepegawaian');

        $this->kasubagTu = $this->makeUser('KasubagTU', '100000000000000004', [
            'pangkat_gol' => 'Penata (III/c)',
            'jabatan' => 'Kasubbag Tata Usaha',
        ]);
        $this->kasubagTu->assignRole('kasubag_tu');

        $this->pegawai = $this->makeUser('Pegawai', '100000000000000005', [
            'unit_kerja_id' => $unit->id,
            'seksi_id' => $seksi->id,
        ]);
        $this->pegawai->assignRole('pegawai');
        $this->pegawai->saldoCuti()->create(['saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y')]);
    }

    private function setUpAdministrasi(): void
    {
        $seksi = Seksi::create(['nama_seksi' => 'Seksi Administrasi']);
        $this->kanitAdm = $this->makeUser('Kanit A', '100000000000000010', [
            'pangkat_gol' => 'Penata Tk.I (III/d)',
            'jabatan' => 'Kepala Unit Administrasi',
        ]);
        $this->kanitAdm->assignRole('kanit');
        $this->kasubagAdm = $this->makeUser('Kasubag A', '100000000000000011', [
            'pangkat_gol' => 'Penata Muda Tk.I (III/b)',
            'jabatan' => 'Kasubbag Umum',
        ]);
        $this->kasubagAdm->assignRole('kasubag');
        $seksi->update(['kepala_seksi_id' => $this->kasubagAdm->id]);

        $unit = UnitKerja::create([
            'nama_unit' => 'Unit Administrasi',
            'jenis' => 'administrasi',
            'seksi_id' => $seksi->id,
            'kepala_unit_id' => $this->kanitAdm->id,
        ]);

        $this->pejabat = $this->makeUser('Pejabat A', '100000000000000012', [
            'pangkat_gol' => 'Pembina (IV/a)',
            'jabatan' => 'Pejabat Berwenang',
        ]);
        $this->pejabat->assignRole('pejabat_berwenang');

        $this->pegawaiAdm = $this->makeUser('Pegawai Adm', '100000000000000020', [
            'unit_kerja_id' => $unit->id,
            'seksi_id' => $seksi->id,
        ]);
        $this->pegawaiAdm->assignRole('pegawai');
        $this->pegawaiAdm->saldoCuti()->create(['saldo_n' => 12, 'saldo_n1' => 0, 'saldo_n2' => 0, 'tahun_berjalan' => date('Y')]);
    }

    private function submitOperasional(): PengajuanCuti
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

        return $pengajuan;
    }

    private function submitAdministrasi(): PengajuanCuti
    {
        $pengajuan = PengajuanCuti::create([
            'user_id' => $this->pegawaiAdm->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'lama_cuti' => 3,
            'alamat_selama_cuti' => 'Rumah',
        ])->fresh();

        return $pengajuan;
    }

    public function test_snapshot_kanit_operasional_tersimpan_saat_disetujui(): void
    {
        Carbon::setTestNow('2026-10-15 09:30:00');

        $pengajuan = $this->submitOperasional();
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();

        $statusBelum = $pengajuan->status;
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();

        $this->assertEquals('menunggu_kanit_kepegawaian', $statusBelum);
        $this->assertEquals('menunggu_kasubag_tu', $pengajuan->status);
        $this->assertEquals('KanitKep', $pengajuan->kanit_nama);
        $this->assertEquals($this->kanitKepegawaian->nip, $pengajuan->kanit_nip);
        $this->assertEquals('Penata Muda (III/a)', $pengajuan->kanit_pangkat);
        $this->assertEquals('Kepala Bagian Kepegawaian', $pengajuan->kanit_jabatan);
        $this->assertTrue($pengajuan->kanit_tanggal_keputusan->eq(Carbon::parse('2026-10-15 09:30:00')));

        Carbon::setTestNow();
    }

    public function test_snapshot_kasubag_operasional_tersimpan_saat_final_disetujui(): void
    {
        $pengajuan = $this->submitOperasional();
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan->update(['keputusan_kasubag_tu' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();

        $this->assertEquals('disetujui', $pengajuan->status);
        $this->assertEquals('KasubagTU', $pengajuan->kasubag_nama);
        $this->assertEquals($this->kasubagTu->nip, $pengajuan->kasubag_nip);
        $this->assertEquals('Penata (III/c)', $pengajuan->kasubag_pangkat);
        $this->assertEquals('Kasubbag Tata Usaha', $pengajuan->kasubag_jabatan);
        $this->assertNotNull($pengajuan->kasubag_tanggal_keputusan);

        // Snapshot Kanit tidak tertimpa oleh keputusan Kasubag TU.
        $this->assertEquals('KanitKep', $pengajuan->kanit_nama);

        // Issue #44 tetap berjalan: final = Kasubag TU, potong saldo sekali.
        $this->assertEquals(9, $this->pegawai->fresh()->saldoCuti->saldo_n);
        $this->assertDatabaseHas('saldo_cuti_ledgers', ['pengajuan_cuti_id' => $pengajuan->id, 'aksi' => 'potong']);
    }

    public function test_snapshot_keputusan_ditolak_dan_ditangguhkan_tetap_tersimpan(): void
    {
        // Ditolak oleh Kanit Kepegawaian
        $ditolak = $this->submitOperasional();
        $ditolak->update(['keputusan_kepala_unit' => 'disetujui']);
        $ditolak->update(['keputusan_kepala_seksi' => 'disetujui']);
        $ditolak->update(['keputusan_kanit_kepegawaian' => 'tidak_disetujui', 'alasan_kanit_kepegawaian' => 'Berkas kurang']);
        $ditolak = $ditolak->fresh();
        $this->assertEquals('ditolak_kanit_kepegawaian', $ditolak->status);
        $this->assertEquals('KanitKep', $ditolak->kanit_nama);
        $this->assertNotNull($ditolak->kanit_tanggal_keputusan);

        // Ditangguhkan oleh Kanit Kepegawaian
        $ditangguhkan = $this->submitOperasional();
        $ditangguhkan->update(['keputusan_kepala_unit' => 'disetujui']);
        $ditangguhkan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $ditangguhkan->update(['keputusan_kanit_kepegawaian' => 'ditangguhkan', 'alasan_kanit_kepegawaian' => 'Menunggu klarifikasi']);
        $ditangguhkan = $ditangguhkan->fresh();
        $this->assertEquals('ditangguhkan', $ditangguhkan->status);
        $this->assertEquals('KanitKep', $ditangguhkan->kanit_nama);
    }

    public function test_flow_administrasi_snapshot_kanit_kasubag_pejabat_dan_pdf(): void
    {
        $pengajuan = $this->submitAdministrasi();
        $this->assertEquals('menunggu_atasan', $pengajuan->status);

        $pengajuan->update(['keputusan_kanit' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('menunggu_atasan', $pengajuan->status);
        $this->assertEquals('Kanit A', $pengajuan->kanit_nama);
        $this->assertEquals($this->kanitAdm->nip, $pengajuan->kanit_nip);

        $pengajuan->update(['keputusan_kasubag' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('menunggu_pejabat', $pengajuan->status);
        $this->assertEquals('Kasubag A', $pengajuan->kasubag_nama);
        $this->assertEquals($this->kasubagAdm->nip, $pengajuan->kasubag_nip);

        $pengajuan->update(['keputusan_pejabat' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('disetujui', $pengajuan->status);
        $this->assertEquals('Pejabat A', $pengajuan->pejabat_nama);
        $this->assertEquals($this->pejabat->nip, $pengajuan->pejabat_nip);
        $this->assertEquals('Pembina (IV/a)', $pengajuan->pejabat_pangkat);
        $this->assertNotNull($pengajuan->pejabat_tanggal_keputusan);

        $this->assertEquals(9, $this->pegawaiAdm->fresh()->saldoCuti->saldo_n);

        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();
        $this->assertStringContainsString('PERTIMBANGAN ATASAN LANGSUNG', $html);
        $this->assertStringContainsString('<u>Kanit A</u>', $html);
        $this->assertStringContainsString('<u>Kasubag A</u>', $html);
        $this->assertStringContainsString('<u>Pejabat A</u>', $html);
    }

    public function test_snapshot_tidak_berubah_saat_profil_approver_berubah(): void
    {
        $pengajuan = $this->submitOperasional();
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();

        // Profil user berubah setelah keputusan dibuat.
        $this->kanitKepegawaian->update([
            'nama' => 'KanitKep Baru',
            'nip' => '100000000000000099',
            'pangkat_gol' => 'Pembina (IV/b)',
            'jabatan' => 'Jabatan Baru',
        ]);

        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('KanitKep', $pengajuan->kanit_nama);
        $this->assertEquals('100000000000000003', $pengajuan->kanit_nip);
        $this->assertEquals('Penata Muda (III/a)', $pengajuan->kanit_pangkat);
        $this->assertEquals('Kepala Bagian Kepegawaian', $pengajuan->kanit_jabatan);

        // PDF tetap menampilkan nama & NIP historis.
        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();
        $this->assertStringContainsString('<u>KanitKep</u>', $html);
        $this->assertStringContainsString('NIP. 100000000000000003', $html);
        $this->assertStringNotContainsString('<u>KanitKep Baru</u>', $html);
    }

    public function test_pdf_administrasi_menampilkan_kanit_lama_setelah_penggantian_struktur(): void
    {
        $pengajuan = $this->submitAdministrasi();
        $pengajuan->update(['keputusan_kanit' => 'disetujui']);
        $pengajuan->update(['keputusan_kasubag' => 'disetujui']);
        $pengajuan->update(['keputusan_pejabat' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('Kanit A', $pengajuan->kanit_nama);

        // Struktur diganti: Kanit A -> Kanit B (updateQuietly agar UnitKerjaObserver
        // tidak melepas role 'kanit' dari user lama yang masih dipakai assertion snapshot).
        $kanitB = $this->makeUser('Kanit B', '100000000000000021');
        $kanitB->assignRole('kanit');
        $pengajuan->unitKerja->updateQuietly(['kepala_unit_id' => $kanitB->id]);

        $pengajuan = $pengajuan->fresh();
        $this->assertNotEquals($pengajuan->kanit_nama, $pengajuan->kanit?->nama);

        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();
        $this->assertStringContainsString('<u>Kanit A</u>', $html);
        $this->assertStringNotContainsString('<u>Kanit B</u>', $html);
    }

    public function test_tanggal_keputusan_tercatat_sesuai_momen_keputusan(): void
    {
        $pengajuan = $this->submitOperasional();
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);

        Carbon::setTestNow('2026-10-20 10:05:00');
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertTrue($pengajuan->kanit_tanggal_keputusan->eq(Carbon::parse('2026-10-20 10:05:00')));

        Carbon::setTestNow('2026-10-21 14:40:00');
        $pengajuan->update(['keputusan_kasubag_tu' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertTrue($pengajuan->kasubag_tanggal_keputusan->eq(Carbon::parse('2026-10-21 14:40:00')));

        Carbon::setTestNow();
    }

    public function test_record_legacy_tanpa_snapshot_pdf_tetap_berhasil(): void
    {
        $pengajuan = PengajuanCuti::create([
            'user_id' => $this->pegawaiAdm->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'lama_cuti' => 3,
            'alamat_selama_cuti' => 'Rumah',
            'status' => 'disetujui',
            'keputusan_kanit' => 'disetujui',
            'keputusan_kasubag' => 'disetujui',
            'keputusan_pejabat' => 'disetujui',
        ])->fresh();

        // Legacy record: tidak ada snapshot tersimpan.
        $this->assertNull($pengajuan->kanit_nama);
        $this->assertNull($pengajuan->kasubag_nama);
        $this->assertNull($pengajuan->pejabat_nama);

        // PDF tetap render via fallback existing tanpa error.
        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();
        $this->assertStringContainsString('PERTIMBANGAN ATASAN LANGSUNG', $html);
        $this->assertStringContainsString('<u>Kanit A</u>', $html);
        $this->assertStringContainsString('<u>Kasubag A</u>', $html);
        $this->assertStringContainsString('<u>Pejabat A</u>', $html);
    }

    public function test_resubmit_menghapus_snapshot_lalu_mengisi_ulang(): void
    {
        $pengajuan = $this->submitOperasional();
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'perubahan', 'alasan_kanit_kepegawaian' => 'Ubah tanggal']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('perubahan', $pengajuan->status);
        $this->assertEquals('KanitKep', $pengajuan->kanit_nama);

        // Pemohon mengirim ulang (resubmit) -> snapshot dibersihkan.
        $this->actingAs($this->pegawai);
        $pengajuan->update([
            'tanggal_mulai' => '2026-10-06',
            'tanggal_selesai' => '2026-10-07',
        ]);
        $pengajuan = $pengajuan->fresh();
        $this->assertNull($pengajuan->kanit_nama);
        $this->assertNull($pengajuan->kanit_tanggal_keputusan);

        // Keputusan versi baru membuat snapshot baru.
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('menunggu_kasubag_tu', $pengajuan->status);
        $this->assertEquals('KanitKep', $pengajuan->kanit_nama);
        $this->assertNotNull($pengajuan->kanit_tanggal_keputusan);

        // Issue #41: aliran dibuka ulang tanpa koreksi saldo ganda.
        $this->assertDatabaseMissing('saldo_cuti_ledgers', ['pengajuan_cuti_id' => $pengajuan->id, 'aksi' => 'koreksi']);
    }
}
