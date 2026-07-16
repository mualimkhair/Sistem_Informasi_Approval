<?php

namespace App\Imports;

use App\Models\SaldoCuti;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class UserImport implements ToCollection, WithHeadingRow
{
    public array $results = [];

    private const SALDO_LIMITS = [
        'saldo_n'                   => ['min' => 0, 'max' => 12],
        'saldo_n1'                  => ['min' => 0, 'max' => 6],
        'saldo_n2'                  => ['min' => 0, 'max' => 12],
        'saldo_cuti_besar'          => ['min' => 0, 'max' => 90],
        'saldo_cuti_sakit'          => ['min' => 0, 'max' => 365],
        'saldo_cuti_melahirkan'     => ['min' => 0, 'max' => 90],
        'saldo_cuti_alasan_penting' => ['min' => 0, 'max' => 30],
    ];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            $this->processRow($row->toArray(), $rowNum);
        }
    }

    private function processRow(array $row, int $rowNum): void
    {
        $rawNip = isset($row['nip']) ? (string) $row['nip'] : '';
        $nip = $this->cleanNip($rawNip);

        if (!$this->isValidNip($nip)) {
            $this->results[] = [
                'row'    => $rowNum,
                'status' => 'gagal',
                'pesan'  => "NIP tidak valid: '{$rawNip}' — kemungkinan rusak akibat format kolom Number di Excel atau bukan tepat 18 digit numerik.",
            ];
            return;
        }

        $nama = trim($row['nama'] ?? '');
        if ($nama === '') {
            $this->results[] = [
                'row'    => $rowNum,
                'status' => 'gagal',
                'pesan'  => "Kolom 'nama' tidak boleh kosong.",
            ];
            return;
        }

        $existing = User::where('nip', $nip)->first();

        if ($existing) {
            $this->results[] = [
                'row'    => $rowNum,
                'status' => 'dilewati',
                'pesan'  => "NIP {$nip} sudah ada di database, dilewati.",
            ];
            return;
        }

        $saldoFields = [
            'saldo_n'                   => $row['saldo_n'] ?? null,
            'saldo_n1'                  => $row['saldo_n_1'] ?? null,
            'saldo_n2'                  => $row['saldo_n_2'] ?? null,
            'saldo_cuti_besar'          => $row['saldo_cuti_besar'] ?? null,
            'saldo_cuti_sakit'          => $row['saldo_cuti_sakit'] ?? null,
            'saldo_cuti_melahirkan'     => $row['saldo_cuti_melahirkan'] ?? null,
            'saldo_cuti_alasan_penting' => $row['saldo_cuti_alasan_penting'] ?? null,
        ];

        $defaults = [
            'saldo_n'                   => 12,
            'saldo_n1'                  => 0,
            'saldo_n2'                  => 0,
            'saldo_cuti_besar'          => 90,
            'saldo_cuti_sakit'          => 365,
            'saldo_cuti_melahirkan'     => 90,
            'saldo_cuti_alasan_penting' => 30,
        ];

        $saldoToSave = [];
        foreach (self::SALDO_LIMITS as $field => $limits) {
            $rawVal = $saldoFields[$field];
            if ($rawVal === null || $rawVal === '') {
                $saldoToSave[$field] = $defaults[$field];
            } else {
                $val = (int) $rawVal;
                if ($val < $limits['min'] || $val > $limits['max']) {
                    $this->results[] = [
                        'row'    => $rowNum,
                        'status' => 'gagal',
                        'pesan'  => "Nilai saldo '{$field}' = {$val} di luar batas wajar ({$limits['min']}–{$limits['max']}).",
                    ];
                    return;
                }
                $saldoToSave[$field] = $val;
            }
        }

        $user = User::create([
            'nip'                  => $nip,
            'nama'                 => $nama,
            'password'             => Hash::make($nip),
            'is_profile_completed' => false,
        ]);

        $user->assignRole('pegawai');

        SaldoCuti::create(array_merge(
            ['user_id' => $user->id, 'tahun_berjalan' => date('Y')],
            $saldoToSave
        ));

        $this->results[] = [
            'row'    => $rowNum,
            'status' => 'berhasil',
            'pesan'  => "User baru NIP {$nip} ({$nama}) berhasil dibuat dengan saldo awal.",
        ];
    }

    private function cleanNip(string $raw): string
    {
        $cleaned = preg_replace('/[\s\x{00A0}]/u', '', $raw);
        $cleaned = preg_replace('/[.\-]/', '', $cleaned);
        return $cleaned;
    }

    private function isValidNip(string $nip): bool
    {
        return (bool) preg_match('/^\d{18}$/', $nip);
    }
}
