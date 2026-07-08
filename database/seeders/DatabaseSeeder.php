<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin',
            'admin',
            'pegawai',
            'kanit',
            'kasubag',
            'pejabat_berwenang'
        ];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $this->call([
            StrukturSeeder::class,
            HariLiburSeeder::class,
        ]);
    }
}
