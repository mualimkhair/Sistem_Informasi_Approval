<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_user_nip_is_unique(): void
    {
        $user1 = User::where('nip', '199001012020121002')->first();
        $this->assertNotNull($user1);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::create([
            'nip' => '199001012020121002',
            'nama' => 'Duplicate',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_user_nip_is_18_digits(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        $this->assertEquals(18, strlen($user->nip));
    }

    public function test_user_has_required_fields(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        
        $this->assertNotNull($user->nip);
        $this->assertNotNull($user->nama);
        $this->assertNotNull($user->password);
    }

    public function test_user_belongs_to_unit_kerja(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        if ($user->unit_kerja_id) {
            $this->assertNotNull($user->unitKerja);
        } else {
            $this->assertNull($user->unitKerja);
        }
    }

    public function test_user_profile_completion_status(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        
        $this->assertIsBool($user->is_profile_completed);
    }

    public function test_user_can_update_profile(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $user->update([
            'alamat' => 'Jl. Updated Address',
            'nomor_telp' => '081234567890',
            'is_profile_completed' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'alamat' => 'Jl. Updated Address',
            'nomor_telp' => '081234567890',
            'is_profile_completed' => true,
        ]);
    }

    public function test_user_has_saldo_cuti_relationship(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $user->saldoCuti());
    }

    public function test_user_has_pengajuan_cutis_relationship(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $user->pengajuanCutis());
    }

    public function test_user_can_have_signature(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        
        $user->update([
            'signature_path' => 'signatures/test-signature.png',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'signature_path' => 'signatures/test-signature.png',
        ]);
    }
}
