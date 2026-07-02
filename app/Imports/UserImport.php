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

        \App\Models\SaldoCuti::updateOrCreate(
            ['user_id' => $user->id],
            [
                'tahun_berjalan' => date('Y'),
                'saldo_n' => $row['saldo_n'] ?? 12,
                'saldo_n1' => $row['saldo_n_1'] ?? 0,
                'saldo_n2' => $row['saldo_n_2'] ?? 0,
                'saldo_cuti_besar' => $row['saldo_cuti_besar'] ?? 90,
                'saldo_cuti_sakit' => $row['saldo_cuti_sakit'] ?? 365,
                'saldo_cuti_melahirkan' => $row['saldo_cuti_melahirkan'] ?? 90,
                'saldo_cuti_alasan_penting' => $row['saldo_cuti_alasan_penting'] ?? 30,
            ]
        );

        return $user;
    }
}
