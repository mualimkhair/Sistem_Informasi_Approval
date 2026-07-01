<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class WorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_complete_approval_workflow(): void
    {
        $pegawai = User::where('nip', '199202022020121002')->first();
        $kanit = User::where('nip', '198003032010121003')->first();
        $kasubag = User::where('nip', '197004042000121004')->first();
        $pejabat = User::where('nip', '196005051990121005')->first();

        $pegawai->assignRole('pegawai');
        $kanit->assignRole('kanit');
        $kasubag->assignRole('kasubag');
        $pejabat->assignRole('pejabat_berwenang');

        SaldoCuti::firstOrCreate(
            ['user_id' => $pegawai->id],
            [
                'saldo_n' => 12,
                'saldo_n1' => 0,
                'saldo_n2' => 0,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $pengajuan = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan keluarga',
            'tanggal_mulai' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(12)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Jl. Liburan No. 123',
            'lama_cuti' => 3,
            'status' => 'menunggu_atasan',
        ]);

        $this->assertEquals('menunggu_atasan', $pengajuan->status);

        $pengajuan->update(['keputusan_kanit' => 'disetujui']);
        $pengajuan->refresh();
        $this->assertEquals('menunggu_atasan', $pengajuan->status);

        $pengajuan->update(['keputusan_kasubag' => 'disetujui']);
        $pengajuan->refresh();
        $this->assertEquals('menunggu_pejabat', $pengajuan->status);

        $pengajuan->update(['keputusan_pejabat' => 'disetujui']);
        $pengajuan->refresh();
        $this->assertEquals('disetujui', $pengajuan->status);

        $saldo = SaldoCuti::where('user_id', $pegawai->id)->first();
        $this->assertEquals(9, $saldo->saldo_n);
    }

    public function test_rejection_by_kanit_stops_workflow(): void
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
            'alasan_kanit' => 'Tidak ada alasan yang jelas',
        ]);
        $pengajuan->refresh();

        $this->assertEquals('ditolak_kanit', $pengajuan->status);
    }

    public function test_rejection_by_pejabat_after_supervisor_approval(): void
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

        $pengajuan->update([
            'keputusan_pejabat' => 'tidak_disetujui',
            'alasan_pejabat' => 'Periode terlalu dekat dengan operasional penting',
        ]);
        $pengajuan->refresh();

        $this->assertEquals('ditolak_pejabat', $pengajuan->status);
    }

    public function test_request_for_changes_allows_resubmission(): void
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
            'keputusan_pejabat' => 'perubahan',
            'alasan_pejabat' => 'Mohon ubah tanggal',
        ]);
        $pengajuan->refresh();

        $this->assertEquals('perubahan', $pengajuan->status);

        $pengajuan->update([
            'tanggal_mulai' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(16)->format('Y-m-d'),
        ]);
        $pengajuan->refresh();

        $this->assertNull($pengajuan->keputusan_kanit);
        $this->assertNull($pengajuan->keputusan_kasubag);
        $this->assertNull($pengajuan->keputusan_pejabat);
    }

    public function test_postponement_status(): void
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
            'keputusan_pejabat' => 'ditangguhkan',
            'alasan_pejabat' => 'Menunggu keputusan lebih lanjut',
        ]);
        $pengajuan->refresh();

        $this->assertEquals('ditangguhkan', $pengajuan->status);
    }

    public function test_multiple_leave_requests_by_same_user(): void
    {
        $pegawai = User::where('nip', '199202022020121002')->first();

        $pengajuan1 = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan 1',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Address 1',
        ]);

        $pengajuan2 = PengajuanCuti::create([
            'user_id' => $pegawai->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Liburan 2',
            'tanggal_mulai' => Carbon::now()->addDays(30)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(32)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Address 2',
        ]);

        $count = PengajuanCuti::where('user_id', $pegawai->id)->count();
        $this->assertEquals(2, $count);
    }
}
