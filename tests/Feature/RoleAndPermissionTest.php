<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UnitKerja;
use App\Models\KelompokKerja;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_super_admin_role_exists(): void
    {
        $role = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
        $this->assertNotNull($role);
    }

    public function test_all_required_roles_exist(): void
    {
        $requiredRoles = ['super_admin', 'admin', 'pegawai', 'kanit', 'kasubag', 'pejabat_berwenang'];
        
        foreach ($requiredRoles as $roleName) {
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
            $this->assertNotNull($role, "Role {$roleName} should exist");
        }
    }

    public function test_user_can_be_assigned_pegawai_role(): void
    {
        $user = User::where('nip', '199202022020121002')->first();
        $user->assignRole('pegawai');
        
        $this->assertTrue($user->hasRole('pegawai'));
    }

    public function test_user_can_be_assigned_kanit_role(): void
    {
        $user = User::where('nip', '198003032010121003')->first();
        $user->assignRole('kanit');
        
        $this->assertTrue($user->hasRole('kanit'));
    }

    public function test_user_can_be_assigned_kasubag_role(): void
    {
        $user = User::where('nip', '197004042000121004')->first();
        $user->assignRole('kasubag');
        
        $this->assertTrue($user->hasRole('kasubag'));
    }

    public function test_user_can_be_assigned_pejabat_role(): void
    {
        $user = User::where('nip', '196005051990121005')->first();
        $user->assignRole('pejabat_berwenang');
        
        $this->assertTrue($user->hasRole('pejabat_berwenang'));
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::where('nip', '199001012020121002')->first();
        $user->assignRole(['super_admin', 'admin']);
        
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_user_without_role_is_guest(): void
    {
        $user = User::factory()->create();
        
        $this->assertEmpty($user->getRoleNames());
    }
}
