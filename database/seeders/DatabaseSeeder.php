<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

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
            'pejabat_berwenang',
            'kanit_kepegawaian',
            'kasubag_tu',
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
