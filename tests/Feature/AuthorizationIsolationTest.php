<?php

namespace Tests\Feature;

use App\Models\PengajuanCuti;
use App\Models\Seksi;
use App\Models\UnitKerja;
use App\Models\User;
use App\Policies\PengajuanCutiPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $kanitA;

    private User $kanitB;

    private User $kasubag;

    private User $kanitKepegawaian;

    private User $kasubagTu;

    private User $pegawaiA;

    private User $pegawaiB;

    private UnitKerja $unitA;

    private UnitKerja $unitB;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'pegawai', 'kanit', 'kasubag', 'pejabat_berwenang', 'kanit_kepegawaian', 'kasubag_tu'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $seksi = Seksi::create(['nama_seksi' => 'Seksi Operasional']);

        $this->kasubag = $this->makeUser('Kasubag', '100000000000000001');
        $this->kasubag->assignRole('kasubag');
        $seksi->update(['kepala_seksi_id' => $this->kasubag->id]);

        $this->kanitA = $this->makeUser('KU A', '100000000000000002');
        $this->kanitA->assignRole('kanit');
        $this->unitA = $this->makeUnit('Unit A', $seksi->id, $this->kanitA->id);

        $this->kanitB = $this->makeUser('KU B', '100000000000000003');
        $this->kanitB->assignRole('kanit');
        $this->unitB = $this->makeUnit('Unit B', $seksi->id, $this->kanitB->id);

        $this->kanitKepegawaian = $this->makeUser('KanitKep', '100000000000000004');
        $this->kanitKepegawaian->assignRole('kanit_kepegawaian');

        $this->kasubagTu = $this->makeUser('KasubagTU', '100000000000000005');
        $this->kasubagTu->assignRole('kasubag_tu');

        $this->pegawaiA = $this->makeUser('Pegawai A', '100000000000000006', ['unit_kerja_id' => $this->unitA->id]);
        $this->pegawaiA->assignRole('pegawai');

        $this->pegawaiB = $this->makeUser('Pegawai B', '100000000000000007', ['unit_kerja_id' => $this->unitB->id]);
        $this->pegawaiB->assignRole('pegawai');
    }

    private function makeUser(string $nama, string $nip, array $extra = []): User
    {
        return User::create(array_merge([
            'nama' => $nama,
            'nip' => $nip,
            'password' => bcrypt('password'),
            'is_profile_completed' => true,
        ], $extra));
    }

    private function makeUnit(string $nama, int $seksiId, ?int $kepalaUnitId): UnitKerja
    {
        return UnitKerja::create([
            'nama_unit' => $nama,
            'jenis' => 'operasional',
            'seksi_id' => $seksiId,
            'kepala_unit_id' => $kepalaUnitId,
        ]);
    }

    private function approvalRecord(User $user, UnitKerja $unit, array $overrides = []): PengajuanCuti
    {
        return PengajuanCuti::withoutEvents(fn () => PengajuanCuti::create(array_merge([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan',
            'tanggal_mulai' => '2026-10-05',
            'tanggal_selesai' => '2026-10-07',
            'lama_cuti' => 3,
            'alamat_selama_cuti' => 'Rumah',
            'tipe_aliran' => 'operasional',
            'unit_kerja_id' => $unit->id,
            'seksi_id' => $unit->seksi_id,
            'status' => 'menunggu_kepala_unit',
            'kepala_unit_id' => $unit->kepala_unit_id,
            'kepala_seksi_id' => $unit->seksi_id ? Seksi::find($unit->seksi_id)?->kepala_seksi_id : null,
            'kanit_kepegawaian_id' => $this->kanitKepegawaian->id,
            'kasubag_tu_id' => $this->kasubagTu->id,
        ], $overrides)));
    }

    public function test_pegawai_hanya_bisa_lihat_pengajuannya_sendiri(): void
    {
        $recA = $this->approvalRecord($this->pegawaiA, $this->unitA);

        $policy = new PengajuanCutiPolicy();

        $this->assertTrue($policy->view($this->pegawaiA, $recA));
        $this->assertFalse($policy->view($this->pegawaiB, $recA));
    }

    public function test_kanit_hanya_berlaku_di_unit_nya_sendiri(): void
    {
        $recA = $this->approvalRecord($this->pegawaiA, $this->unitA);
        $recB = $this->approvalRecord($this->pegawaiB, $this->unitB);

        $scope = PengajuanCuti::forApprover($this->kanitA)->where('status', 'menunggu_kepala_unit')->pluck('id');

        $this->assertTrue($scope->contains($recA->id));
        $this->assertFalse($scope->contains($recB->id));
    }

    public function test_kanit_tidak_sah_memproses_unit_lain(): void
    {
        $recB = $this->approvalRecord($this->pegawaiB, $this->unitB);

        $policy = new PengajuanCutiPolicy();

        $this->assertFalse($policy->view($this->kanitA, $recB));
    }

    public function test_kasubag_tu_hanya_menangkap_tahap_akhir_scope_sendiri(): void
    {
        $recStage3 = $this->approvalRecord($this->pegawaiA, $this->unitA, [
            'status' => 'menunggu_kanit_kepegawaian',
        ]);
        $recStage4 = $this->approvalRecord($this->pegawaiA, $this->unitA, [
            'status' => 'menunggu_kasubag_tu',
        ]);

        $scopeStage4 = PengajuanCuti::forApprover($this->kasubagTu)
            ->where('status', 'menunggu_kasubag_tu')
            ->where('kasubag_tu_id', $this->kasubagTu->id)
            ->pluck('id');

        $this->assertTrue($scopeStage4->contains($recStage4->id));
        $this->assertFalse($scopeStage4->contains($recStage3->id));
    }

    public function test_admin_dan_super_admin_melihat_semua(): void
    {
        $superAdmin = $this->makeUser('Super Admin', '000000000000000000');
        $superAdmin->assignRole('super_admin');

        $recA = $this->approvalRecord($this->pegawaiA, $this->unitA);
        $recB = $this->approvalRecord($this->pegawaiB, $this->unitB);

        $scope = PengajuanCuti::forApprover($superAdmin)->pluck('id');

        $this->assertTrue($scope->contains($recA->id));
        $this->assertTrue($scope->contains($recB->id));
    }

    public function test_pejabat_berwenang_tidak_dapat_membuat_pengajuan(): void
    {
        $pejabat = $this->makeUser('Pejabat Berwenang', '100000000000000008');
        $pejabat->assignRole('pejabat_berwenang');

        $this->assertFalse((new PengajuanCutiPolicy())->create($pejabat));
    }
}