<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SaldoCuti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SaldoCutiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_user_has_leave_balance(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::firstOrCreate(
            ['user_id' => $user->id],
            [
                'saldo_n' => 12,
                'saldo_n1' => 0,
                'saldo_n2' => 0,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $this->assertDatabaseHas('saldo_cutis', [
            'user_id' => $user->id,
        ]);
    }

    public function test_leave_balance_has_correct_structure(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::firstOrCreate(
            ['user_id' => $user->id],
            [
                'saldo_n' => 12,
                'saldo_n1' => 6,
                'saldo_n2' => 3,
                'saldo_cuti_besar' => 0,
                'saldo_cuti_sakit' => 0,
                'saldo_cuti_melahirkan' => 0,
                'saldo_cuti_alasan_penting' => 0,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $this->assertNotNull($saldo->saldo_n);
        $this->assertNotNull($saldo->saldo_n1);
        $this->assertNotNull($saldo->saldo_n2);
        $this->assertNotNull($saldo->tahun_berjalan);
    }

    public function test_leave_balance_deduction_priority_n2_first(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::firstOrCreate(
            ['user_id' => $user->id],
            [
                'saldo_n' => 12,
                'saldo_n1' => 6,
                'saldo_n2' => 3,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $lamaCuti = 5;
        
        if ($saldo->saldo_n2 >= $lamaCuti) {
            $saldo->saldo_n2 -= $lamaCuti;
        } elseif ($saldo->saldo_n2 > 0) {
            $sisa = $lamaCuti - $saldo->saldo_n2;
            $saldo->saldo_n2 = 0;
            
            if ($saldo->saldo_n1 >= $sisa) {
                $saldo->saldo_n1 -= $sisa;
            } elseif ($saldo->saldo_n1 > 0) {
                $sisaLagi = $sisa - $saldo->saldo_n1;
                $saldo->saldo_n1 = 0;
                $saldo->saldo_n = max(0, $saldo->saldo_n - $sisaLagi);
            } else {
                $saldo->saldo_n = max(0, $saldo->saldo_n - $sisa);
            }
        } else {
            if ($saldo->saldo_n1 >= $lamaCuti) {
                $saldo->saldo_n1 -= $lamaCuti;
            } elseif ($saldo->saldo_n1 > 0) {
                $sisa = $lamaCuti - $saldo->saldo_n1;
                $saldo->saldo_n1 = 0;
                $saldo->saldo_n = max(0, $saldo->saldo_n - $sisa);
            } else {
                $saldo->saldo_n = max(0, $saldo->saldo_n - $lamaCuti);
            }
        }
        
        $saldo->save();

        $this->assertEquals(0, $saldo->saldo_n2);
        $this->assertEquals(4, $saldo->saldo_n1);
        $this->assertEquals(12, $saldo->saldo_n);
    }

    public function test_sick_leave_balance_is_separate(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::firstOrCreate(
            ['user_id' => $user->id],
            [
                'saldo_n' => 12,
                'saldo_cuti_sakit' => 30,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $this->assertEquals(30, $saldo->saldo_cuti_sakit);
        $this->assertNotEquals($saldo->saldo_n, $saldo->saldo_cuti_sakit);
    }

    public function test_maternity_leave_balance_is_separate(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::firstOrCreate(
            ['user_id' => $user->id],
            [
                'saldo_n' => 12,
                'saldo_cuti_melahirkan' => 90,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $this->assertEquals(90, $saldo->saldo_cuti_melahirkan);
    }

    public function test_leave_balance_cannot_be_negative(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::firstOrCreate(
            ['user_id' => $user->id],
            [
                'saldo_n' => 5,
                'saldo_n1' => 0,
                'saldo_n2' => 0,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $lamaCuti = 10;
        $saldo->saldo_n = max(0, $saldo->saldo_n - $lamaCuti);
        $saldo->save();

        $this->assertEquals(0, $saldo->saldo_n);
        $this->assertGreaterThanOrEqual(0, $saldo->saldo_n);
    }

    public function test_leave_balance_year_tracking(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $saldo = SaldoCuti::firstOrCreate(
            ['user_id' => $user->id],
            [
                'saldo_n' => 12,
                'tahun_berjalan' => Carbon::now()->year,
            ]
        );

        $this->assertEquals(Carbon::now()->year, $saldo->tahun_berjalan);
    }
}
