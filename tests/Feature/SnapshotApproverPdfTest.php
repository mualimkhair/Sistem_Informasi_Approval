<?php

namespace Tests\Feature;

use App\Models\PengajuanCuti;
use App\Models\Seksi;
use App\Models\UnitKerja;
use App\Models\User;
use App\Models\BlangkoCuti;
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
        $this->actingAs($this->pegawai);
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
        $this->actingAs($this->pegawaiAdm);
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
        $this->actingAs($this->kepalaUnit);
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        
        $this->actingAs($this->kepalaSeksi);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();

        $statusBelum = $pengajuan->status;
        
        $this->actingAs($this->kanitKepegawaian);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();

        $this->assertEquals('menunggu_kanit_kepegawaian', $statusBelum);
        $this->assertEquals('menunggu_kasubag_tu', $pengajuan->status);
        $this->assertEquals('KanitKep', $pengajuan->kanit_kepegawaian_nama);
        $this->assertEquals($this->kanitKepegawaian->nip, $pengajuan->kanit_kepegawaian_nip);
        $this->assertEquals('Penata Muda (III/a)', $pengajuan->kanit_kepegawaian_pangkat);
        $this->assertEquals('Kepala Bagian Kepegawaian', $pengajuan->kanit_kepegawaian_jabatan);
        $this->assertTrue($pengajuan->kanit_kepegawaian_tanggal_keputusan->eq(Carbon::parse('2026-10-15 09:30:00')));

        Carbon::setTestNow();
    }

    public function test_snapshot_kasubag_operasional_tersimpan_saat_final_disetujui(): void
    {
        $pengajuan = $this->submitOperasional();
        
        $this->actingAs($this->kepalaUnit);
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        
        $this->actingAs($this->kepalaSeksi);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        
        $this->actingAs($this->kanitKepegawaian);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        
        $this->actingAs($this->kasubagTu);
        $pengajuan->update(['keputusan_kasubag_tu' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();

        $this->assertEquals('disetujui', $pengajuan->status);
        $this->assertEquals('KasubagTU', $pengajuan->kasubag_tu_nama);
        $this->assertEquals($this->kasubagTu->nip, $pengajuan->kasubag_tu_nip);
        $this->assertEquals('Penata (III/c)', $pengajuan->kasubag_tu_pangkat);
        $this->assertEquals('Kasubbag Tata Usaha', $pengajuan->kasubag_tu_jabatan);
        $this->assertNotNull($pengajuan->kasubag_tu_tanggal_keputusan);

        // Snapshot Kanit Kepegawaian tidak tertimpa
        $this->assertEquals('KanitKep', $pengajuan->kanit_kepegawaian_nama);
    }

    public function test_snapshot_keputusan_ditolak_dan_ditangguhkan_tetap_tersimpan(): void
    {
        $ditolak = $this->submitOperasional();
        $this->actingAs($this->kepalaUnit);
        $ditolak->update(['keputusan_kepala_unit' => 'disetujui']);
        $this->actingAs($this->kepalaSeksi);
        $ditolak->update(['keputusan_kepala_seksi' => 'disetujui']);
        
        $this->actingAs($this->kanitKepegawaian);
        $ditolak->update(['keputusan_kanit_kepegawaian' => 'tidak_disetujui', 'alasan_kanit_kepegawaian' => 'Berkas kurang']);
        $ditolak = $ditolak->fresh();
        
        $this->assertEquals('ditolak_kanit_kepegawaian', $ditolak->status);
        $this->assertEquals('KanitKep', $ditolak->kanit_kepegawaian_nama);
        $this->assertNotNull($ditolak->kanit_kepegawaian_tanggal_keputusan);
    }

    public function test_flow_administrasi_snapshot_kanit_kasubag_pejabat_dan_pdf(): void
    {
        $pengajuan = $this->submitAdministrasi();
        $this->assertEquals('menunggu_atasan', $pengajuan->status);

        $this->actingAs($this->kanitAdm);
        $pengajuan->update(['keputusan_kanit' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('menunggu_atasan', $pengajuan->status);
        $this->assertEquals('Kanit A', $pengajuan->kanit_nama);
        $this->assertEquals($this->kanitAdm->nip, $pengajuan->kanit_nip);

        $this->actingAs($this->kasubagAdm);
        $pengajuan->update(['keputusan_kasubag' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        $this->assertEquals('disetujui', $pengajuan->status);
        $this->assertEquals('Kasubag A', $pengajuan->kasubag_nama);
        $this->assertEquals($this->kasubagAdm->nip, $pengajuan->kasubag_nip);

        // Blangko Cuti dibuat karena status disetujui
        $blangko = BlangkoCuti::where('pengajuan_cuti_id', $pengajuan->id)->first();
        $this->assertNotNull($blangko);
        
        // Pejabat approve Blangko
        $this->actingAs($this->pejabat);
        $blangko->update(['status' => 'disetujui']);
        $blangko = $blangko->fresh();
        
        $this->assertEquals('disetujui', $blangko->status);
        $this->assertEquals('Pejabat A', $blangko->kabandara_nama);
        $this->assertEquals($this->pejabat->nip, $blangko->kabandara_nip);
        $this->assertEquals('Pembina (IV/a)', $blangko->kabandara_pangkat);
        $this->assertNotNull($blangko->tanggal_keputusan);

        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();
        $this->assertStringContainsString('PERTIMBANGAN ATASAN LANGSUNG', $html);
        $this->assertStringContainsString('<u>Kanit A</u>', $html);
        $this->assertStringContainsString('<u>Kasubag A</u>', $html);
        $this->assertStringContainsString('<u>Pejabat A</u>', $html);
    }

    public function test_snapshot_tidak_berubah_saat_profil_approver_berubah(): void
    {
        $pengajuan = $this->submitOperasional();
        
        $this->actingAs($this->kepalaUnit);
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $this->actingAs($this->kepalaSeksi);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $this->actingAs($this->kanitKepegawaian);
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
        $this->assertEquals('KanitKep', $pengajuan->kanit_kepegawaian_nama);
        $this->assertEquals('100000000000000003', $pengajuan->kanit_kepegawaian_nip);
        $this->assertEquals('Penata Muda (III/a)', $pengajuan->kanit_kepegawaian_pangkat);
        $this->assertEquals('Kepala Bagian Kepegawaian', $pengajuan->kanit_kepegawaian_jabatan);

        // PDF tetap menampilkan nama & NIP historis.
        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan])->render();
        $this->assertStringContainsString('<u>KanitKep</u>', $html);
        $this->assertStringContainsString('NIP. 100000000000000003', $html);
        $this->assertStringNotContainsString('<u>KanitKep Baru</u>', $html);
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
            // no snapshot fields set
        ])->fresh();
        
        BlangkoCuti::where('pengajuan_cuti_id', $pengajuan->id)->firstOrFail()->update([
            'status' => 'disetujui',
            'kabandara_id' => $this->pejabat->id,
            // no snapshot fields set
        ]);

        // Legacy record: tidak ada snapshot tersimpan.
        $this->assertNull($pengajuan->kanit_nama);
        $this->assertNull($pengajuan->kasubag_nama);

        // PDF tetap render via fallback existing tanpa error.
        $html = view('pdf.cetak-cuti', ['pengajuanCuti' => $pengajuan->fresh()])->render();
        $this->assertStringContainsString('PERTIMBANGAN ATASAN LANGSUNG', $html);
        $this->assertStringContainsString('<u>Kanit A</u>', $html);
        $this->assertStringContainsString('<u>Kasubag A</u>', $html);
        $this->assertStringContainsString('<u>Pejabat A</u>', $html);
    }

    public function test_resubmit_menghapus_snapshot_lalu_mengisi_ulang(): void
    {
        $pengajuan = $this->submitOperasional();
        
        $this->actingAs($this->kepalaUnit);
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        
        $this->actingAs($this->kepalaSeksi);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        
        $this->actingAs($this->kanitKepegawaian);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'perubahan', 'alasan_kanit_kepegawaian' => 'Ubah tanggal']);
        $pengajuan = $pengajuan->fresh();
        
        $this->assertEquals('perubahan', $pengajuan->status);
        $this->assertEquals('KanitKep', $pengajuan->kanit_kepegawaian_nama);

        // Pemohon mengirim ulang (resubmit) -> snapshot dibersihkan.
        $this->actingAs($this->pegawai);
        $pengajuan->update([
            'tanggal_mulai' => '2026-10-06',
            'tanggal_selesai' => '2026-10-07',
        ]);
        $pengajuan = $pengajuan->fresh();
        $this->assertNull($pengajuan->kanit_kepegawaian_nama);
        $this->assertNull($pengajuan->kanit_kepegawaian_tanggal_keputusan);

        // Keputusan versi baru membuat snapshot baru.
        $this->actingAs($this->kepalaUnit);
        $pengajuan->update(['keputusan_kepala_unit' => 'disetujui']);
        $this->actingAs($this->kepalaSeksi);
        $pengajuan->update(['keputusan_kepala_seksi' => 'disetujui']);
        $this->actingAs($this->kanitKepegawaian);
        $pengajuan->update(['keputusan_kanit_kepegawaian' => 'disetujui']);
        $pengajuan = $pengajuan->fresh();
        
        $this->assertEquals('menunggu_kasubag_tu', $pengajuan->status);
        $this->assertEquals('KanitKep', $pengajuan->kanit_kepegawaian_nama);
        $this->assertNotNull($pengajuan->kanit_kepegawaian_tanggal_keputusan);
    }
}
