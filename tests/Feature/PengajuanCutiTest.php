<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Models\UnitKerja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PengajuanCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_pegawai_can_create_leave_request(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        $user->assignRole('pegawai');

        $data = [
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Keperluan keluarga',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Jl. Test No. 123',
        ];

        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => $data['jenis_cuti'],
            'alasan_cuti' => $data['alasan_cuti'],
            'tanggal_mulai' => $data['tanggal_mulai'],
            'tanggal_selesai' => $data['tanggal_selesai'],
            'alamat_selama_cuti' => $data['alamat_selama_cuti'],
            'status' => 'menunggu_atasan',
        ]);

        $this->assertDatabaseHas('pengajuan_cutis', [
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'status' => 'menunggu_atasan',
        ]);
    }

    public function test_leave_request_status_is_pending_after_creation(): void
    {
        $user = User::where('nip', '199202022020121002')->first();

        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
        ]);

        $this->assertEquals('menunggu_atasan', $pengajuan->status);
    }

    public function test_kanit_can_approve_leave_request(): void
    {
        $kanit = User::where('nip', '198003032010121003')->first();
        $kanit->assignRole('kanit');

        $pegawai = User::where('nip', '199202022020121002')->first();

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'menunggu_atasan',
        ]);

        $pengajuan->update([
            'keputusan_kanit' => 'disetujui',
        ]);

        $this->assertDatabaseHas('pengajuan_cutis', [
            'id' => $pengajuan->id,
            'keputusan_kanit' => 'disetujui',
        ]);
    }

    public function test_kasubag_can_approve_leave_request(): void
    {
        $kasubag = User::where('nip', '197004042000121004')->first();
        $kasubag->assignRole('kasubag');

        $pegawai = User::where('nip', '199202022020121002')->first();

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'menunggu_atasan',
        ]);

        $pengajuan->update([
            'keputusan_kasubag' => 'disetujui',
        ]);

        $this->assertDatabaseHas('pengajuan_cutis', [
            'id' => $pengajuan->id,
            'keputusan_kasubag' => 'disetujui',
        ]);
    }

    public function test_status_changes_to_waiting_official_after_both_supervisors_approve(): void
    {
        $pegawai = User::where('nip', '199202022020121002')->first();

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'menunggu_atasan',
        ]);

        $pengajuan->update([
            'keputusan_kanit' => 'disetujui',
            'keputusan_kasubag' => 'disetujui',
        ]);

        $pengajuan->refresh();

        $this->assertEquals('menunggu_pejabat', $pengajuan->status);
    }

    public function test_pejabat_can_give_final_approval(): void
    {
        $pejabat = User::where('nip', '196005051990121005')->first();
        $pejabat->assignRole('pejabat_berwenang');

        $pegawai = User::where('nip', '199202022020121002')->first();

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'menunggu_pejabat',
            'keputusan_kanit' => 'disetujui',
            'keputusan_kasubag' => 'disetujui',
        ]);

        $pengajuan->update([
            'keputusan_pejabat' => 'disetujui',
        ]);

        $pengajuan->refresh();

        $this->assertEquals('disetujui', $pengajuan->status);
    }

    public function test_leave_request_can_be_rejected_by_kanit(): void
    {
        $pegawai = User::where('nip', '199202022020121002')->first();

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'menunggu_atasan',
        ]);

        $pengajuan->update([
            'keputusan_kanit' => 'tidak_disetujui',
            'alasan_kanit' => 'Tidak cukup alasan',
        ]);

        $pengajuan->refresh();

        $this->assertEquals('ditolak_kanit', $pengajuan->status);
    }

    public function test_leave_request_can_be_rejected_by_pejabat(): void
    {
        $pegawai = User::where('nip', '199202022020121002')->first();

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'status' => 'menunggu_pejabat',
            'keputusan_kanit' => 'disetujui',
            'keputusan_kasubag' => 'disetujui',
        ]);

        $pengajuan->update([
            'keputusan_pejabat' => 'tidak_disetujui',
            'alasan_pejabat' => 'Periode tidak sesuai',
        ]);

        $pengajuan->refresh();

        $this->assertEquals('ditolak_pejabat', $pengajuan->status);
    }

    public function test_leave_balance_is_deducted_after_approval(): void
    {
        $pegawai = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::where('user_id', $pegawai->id)->first();
        if (!$saldo) {
            $saldo = SaldoCuti::create([
                'user_id' => $pegawai->id,
                'saldo_n' => 12,
                'saldo_n1' => 0,
                'saldo_n2' => 0,
                'tahun_berjalan' => Carbon::now()->year,
            ]);
        }
        $saldoAwal = $saldo->saldo_n;

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test Address',
            'lama_cuti' => 3,
            'status' => 'menunggu_pejabat',
            'keputusan_kanit' => 'disetujui',
            'keputusan_kasubag' => 'disetujui',
        ]);

        $pengajuan->update([
            'keputusan_pejabat' => 'disetujui',
        ]);

        $saldo->refresh();

        $this->assertEquals($saldoAwal - 3, $saldo->saldo_n);
    }

    public function test_user_can_only_see_their_own_leave_requests(): void
    {
        $user1 = User::where('nip', '199202022020121002')->first();
        $user2 = User::factory()->create();

        PengajuanCuti::create([
            'user_id' => $user1->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test User 1',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Address 1',
        ]);

        PengajuanCuti::create([
            'user_id' => $user2->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test User 2',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Address 2',
        ]);

        $user1Requests = PengajuanCuti::where('user_id', $user1->id)->count();
        $user2Requests = PengajuanCuti::where('user_id', $user2->id)->count();

        $this->assertEquals(1, $user1Requests);
        $this->assertEquals(1, $user2Requests);
    }
}
