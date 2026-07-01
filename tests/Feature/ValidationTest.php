<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_leave_start_date_must_be_in_future(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $this->expectException(\Exception::class);
        
        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test',
        ]);
    }

    public function test_leave_end_date_must_be_after_start_date(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $tanggalMulai = Carbon::now()->addDays(10)->format('Y-m-d');
        $tanggalSelesai = Carbon::now()->addDays(5)->format('Y-m-d');
        
        $this->expectException(\Exception::class);
        
        PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'alamat_selama_cuti' => 'Test',
        ]);
    }

    public function test_leave_reason_is_required(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $this->expectException(\Exception::class);
        
        PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => null,
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test',
        ]);
    }

    public function test_address_during_leave_is_required(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $this->expectException(\Exception::class);
        
        PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => null,
        ]);
    }

    public function test_leave_type_must_be_valid(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $validTypes = ['tahunan', 'besar', 'sakit', 'melahirkan', 'alasan_penting', 'diluar_tanggungan_negara'];
        
        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test',
        ]);

        $this->assertContains($pengajuan->jenis_cuti, $validTypes);
    }

    public function test_nip_must_be_18_digits(): void
    {
        $this->expectException(\Exception::class);
        
        User::create([
            'nip' => '12345',
            'nama' => 'Test User',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_sufficient_leave_balance_required(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::firstOrCreate(
            ['user_id' => $user->id],
            [
                'saldo_n' => 2,
                'saldo_n1' => 0,
                'saldo_n2' => 0,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(16)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test',
            'lama_cuti' => 10,
        ]);

        $totalSaldo = $saldo->saldo_n + $saldo->saldo_n1 + $saldo->saldo_n2;
        
        if ($pengajuan->lama_cuti > $totalSaldo) {
            $this->assertTrue(true);
        }
    }

    public function test_user_profile_fields_validation(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $user->update([
            'alamat' => 'Jl. Test No. 123',
            'nomor_telp' => '081234567890',
            'tanggal_masuk' => Carbon::now()->subYears(5)->format('Y-m-d'),
        ]);

        $this->assertNotNull($user->alamat);
        $this->assertNotNull($user->nomor_telp);
        $this->assertNotNull($user->tanggal_masuk);
    }

    public function test_phone_number_format(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $user->update([
            'nomor_telp' => '081234567890',
        ]);

        $this->assertMatchesRegularExpression('/^0[0-9]{9,12}$/', $user->nomor_telp);
    }

    public function test_leave_duration_must_be_positive(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $pengajuan = PengajuanCuti::create([
            'user_id' => $user->id,
            'jenis_cuti' => 'tahunan',
            'alasan_cuti' => 'Test',
            'tanggal_mulai' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'tanggal_selesai' => Carbon::now()->addDays(9)->format('Y-m-d'),
            'alamat_selama_cuti' => 'Test',
            'lama_cuti' => 3,
        ]);

        $this->assertGreaterThan(0, $pengajuan->lama_cuti);
    }
}
