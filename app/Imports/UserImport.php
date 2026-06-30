<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UserImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (!isset($row['nip']) || !isset($row['nama'])) {
            return null;
        }

        $user = User::firstOrCreate(
            ['nip' => $row['nip']],
            [
                'nama' => $row['nama'],
                'password' => Hash::make($row['nip']),
                'is_profile_completed' => false,
            ]
        );

        if (!$user->hasRole('pegawai')) {
            $user->assignRole('pegawai');
        }

        return $user;
    }
}
