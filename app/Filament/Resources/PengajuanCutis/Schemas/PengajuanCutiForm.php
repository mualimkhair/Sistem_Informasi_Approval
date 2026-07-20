<?php

namespace App\Filament\Resources\PengajuanCutis\Schemas;

use App\Models\KelompokKerja;
use App\Services\CutiService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PengajuanCutiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pegawai')
                    ->schema([
                        TextInput::make('nama_display')
                            ->label('Nama')
                            ->default(fn() => Auth::user()->nama)
                            ->formatStateUsing(fn($record) => $record ? $record->user->nama : Auth::user()->nama)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('nip_display')
                            ->label('NIP')
                            ->default(fn() => Auth::user()->nip)
                            ->formatStateUsing(fn($record) => $record ? $record->user->nip : Auth::user()->nip)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('jabatan_display')
                            ->label('Jabatan')
                            ->default(fn() => Auth::user()->jabatan)
                            ->formatStateUsing(fn($record) => $record ? $record->user->jabatan : Auth::user()->jabatan)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('unit_kerja_display')
                            ->label('Unit Kerja')
                            ->default(fn() => Auth::user()->unitKerja->nama_unit ?? '-')
                            ->formatStateUsing(fn($record) => $record ? ($record->user->unitKerja->nama_unit ?? '-') : (Auth::user()->unitKerja->nama_unit ?? '-'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('masa_kerja_display')
                            ->label('Masa Kerja')
                            ->default(fn() => Auth::user()->tanggal_masuk ? Auth::user()->tanggal_masuk->diffInYears(now()) . ' Tahun' : '0 Tahun')
                            ->formatStateUsing(function ($record) {
                                $u = $record ? $record->user : Auth::user();
                                return $u->tanggal_masuk ? $u->tanggal_masuk->diffInYears(now()) . ' Tahun' : '0 Tahun';
                            })
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(3),

                Section::make('Detail Pengajuan')
                    ->schema([
                        Hidden::make('user_id')->default(fn() => Auth::id()),

                        Select::make('kelompok_kerja_id')
                            ->label('Kelompok Kerja')
                            ->options(function (?\App\Models\PengajuanCuti $record) {
                                $owner = $record ? $record->user : Auth::user();
                                if ($owner->unitKerja?->jenis === 'operasional') {
                                    return KelompokKerja::where('unit_kerja_id', $owner->unit_kerja_id)->pluck('nama_kelompok', 'id');
                                }
                                return [];
                            })
                            ->searchable()
                            ->visible(fn(?\App\Models\PengajuanCuti $record) => ($record ? $record->user : Auth::user())->unitKerja?->jenis === 'operasional')
                            ->required(fn(?\App\Models\PengajuanCuti $record) => ($record ? $record->user : Auth::user())->unitKerja?->jenis === 'operasional')
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                self::kalkulasiLamaCuti($get, $set);
                            }),

                        Select::make('jenis_cuti')
                            ->label('Jenis Cuti')
                            ->options([
                                'tahunan' => 'Cuti Tahunan',
                                'besar' => 'Cuti Besar',
                                'sakit' => 'Cuti Sakit',
                                'melahirkan' => 'Cuti Melahirkan',
                                'alasan_penting' => 'Cuti Alasan Penting',
                                'diluar_tanggungan_negara' => 'Cuti Diluar Tanggungan Negara',
                            ])
                            ->searchable()
                            ->required()
                            ->disabled(fn(?\App\Models\PengajuanCuti $record) => $record && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('super_admin')))
                            ->dehydrated(fn(?\App\Models\PengajuanCuti $record) => !($record && (Auth::user()->hasRole('admin') || Auth::user()->hasRole('super_admin'))))
                            ->live(),

                        Textarea::make('alasan_cuti')
                            ->label('Alasan Cuti')
                            ->required(),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->minDate(fn (string $operation, ?\App\Models\PengajuanCuti $record) => match (true) {
                                $operation === 'create' => today(),
                                $operation === 'edit' && Auth::user()->hasRole(['admin', 'super_admin']) => Carbon::parse('2000-01-01'),
                                $operation === 'edit' && $record => Carbon::parse($record->getOriginal('tanggal_mulai'))->startOfDay()->lt(today()) ? Carbon::parse($record->getOriginal('tanggal_mulai'))->startOfDay() : today(),
                                default => today(),
                            })
                            ->live()
                            ->rules([
                                fn(string $operation, $get, $record) => function (string $attribute, $value, \Closure $fail) use ($operation, $get, $record) {
                                    $date = Carbon::parse($value);
                                    
                                    $user = Auth::user();
                                    $konteks = 'create';
                                    if ($operation === 'edit') {
                                        $konteks = $user->hasRole(['admin', 'super_admin']) ? 'koreksi_admin' : 'edit_pegawai';
                                    }

                                    $shouldValidateTime = true;
                                    if ($konteks === 'edit_pegawai' && $record) {
                                        $originalDate = Carbon::parse($record->getOriginal('tanggal_mulai'))->startOfDay();
                                        if ($date->startOfDay()->equalTo($originalDate)) {
                                            $shouldValidateTime = false;
                                        }
                                    }

                                    if ($shouldValidateTime) {
                                        if (!\App\Services\CutiService::validasiTanggal($date, $konteks)) {
                                            if ($konteks === 'koreksi_admin') {
                                                $fail("Tanggal tidak wajar (terlalu lampau).");
                                            } else {
                                                $fail("Tanggal tidak boleh sebelum hari ini.");
                                            }
                                        }
                                    }

                                    $owner = $record ? $record->user : $user;
                                    $unitKerja = $owner->unitKerja;
                                    $kelompokKerja = $get('kelompok_kerja_id') ? KelompokKerja::find($get('kelompok_kerja_id')) : null;
                                    if (\App\Services\CutiService::hitungLamaCuti($date, $date, $unitKerja, $kelompokKerja) === 0) {
                                        $fail('Tanggal tidak boleh jatuh pada hari libur / jadwal libur Anda.');
                                    }
                                },
                            ])
                            ->afterStateUpdated(function ($state, $get, $set, $record) {
                                self::kalkulasiLamaCuti($get, $set, $record);
                            }),

                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->minDate(fn (string $operation, ?\App\Models\PengajuanCuti $record) => match (true) {
                                $operation === 'create' => today(),
                                $operation === 'edit' && Auth::user()->hasRole(['admin', 'super_admin']) => Carbon::parse('2000-01-01'),
                                $operation === 'edit' && $record => Carbon::parse($record->getOriginal('tanggal_selesai'))->startOfDay()->lt(today()) ? Carbon::parse($record->getOriginal('tanggal_selesai'))->startOfDay() : today(),
                                default => today(),
                            })
                            ->afterOrEqual('tanggal_mulai')
                            ->live()
                            ->rules([
                                fn(string $operation, $get, $record) => function (string $attribute, $value, \Closure $fail) use ($operation, $get, $record) {
                                    $date = Carbon::parse($value);
                                    
                                    $user = Auth::user();
                                    $konteks = 'create';
                                    if ($operation === 'edit') {
                                        $konteks = $user->hasRole(['admin', 'super_admin']) ? 'koreksi_admin' : 'edit_pegawai';
                                    }

                                    $shouldValidateTime = true;
                                    if ($konteks === 'edit_pegawai' && $record) {
                                        $originalDate = Carbon::parse($record->getOriginal('tanggal_selesai'))->startOfDay();
                                        if ($date->startOfDay()->equalTo($originalDate)) {
                                            $shouldValidateTime = false;
                                        }
                                    }

                                    if ($shouldValidateTime) {
                                        if (!\App\Services\CutiService::validasiTanggal($date, $konteks)) {
                                            if ($konteks === 'koreksi_admin') {
                                                $fail("Tanggal tidak wajar (terlalu lampau).");
                                            } else {
                                                $fail("Tanggal tidak boleh sebelum hari ini.");
                                            }
                                        }
                                    }

                                    $owner = $record ? $record->user : $user;
                                    $unitKerja = $owner->unitKerja;
                                    $kelompokKerja = $get('kelompok_kerja_id') ? KelompokKerja::find($get('kelompok_kerja_id')) : null;
                                    if (\App\Services\CutiService::hitungLamaCuti($date, $date, $unitKerja, $kelompokKerja) === 0) {
                                        $fail('Tanggal tidak boleh jatuh pada hari libur / jadwal libur Anda.');
                                    }
                                },
                            ])
                            ->afterStateUpdated(function ($state, $get, $set, $record) {
                                self::kalkulasiLamaCuti($get, $set, $record);
                            }),

                        TextInput::make('lama_cuti')
                            ->label('Lama Cuti (Hari Kerja)')
                            ->numeric()
                            ->readOnly()
                            ->required(),

                        Placeholder::make('tanggal_alert')
                            ->label('Tanggal Libur Dikecualikan')
                            ->content(fn($get, $record): string => self::invalidDateSummary($get, $record))
                            ->columnSpanFull(),

                        \Filament\Schemas\Components\Fieldset::make('Informasi Sisa Saldo Cuti')
                            ->schema([
                                Placeholder::make('sisa_n2')
                                    ->label('Saldo N-2')
                                    ->content(fn($get, ?\App\Models\PengajuanCuti $record) => self::getSimulasiSaldo($get, $record)['n2']),
                                Placeholder::make('sisa_n1')
                                    ->label('Saldo N-1')
                                    ->content(fn($get, ?\App\Models\PengajuanCuti $record) => self::getSimulasiSaldo($get, $record)['n1']),
                                Placeholder::make('sisa_n')
                                    ->label('Saldo N')
                                    ->content(fn($get, ?\App\Models\PengajuanCuti $record) => self::getSimulasiSaldo($get, $record)['n']),
                                Placeholder::make('sisa_besar')
                                    ->label('Cuti Besar')
                                    ->content(fn($get, ?\App\Models\PengajuanCuti $record) => self::getSimulasiSaldo($get, $record)['besar']),
                                Placeholder::make('sisa_sakit')
                                    ->label('Cuti Sakit')
                                    ->content(fn($get, ?\App\Models\PengajuanCuti $record) => self::getSimulasiSaldo($get, $record)['sakit']),
                                Placeholder::make('sisa_melahirkan')
                                    ->label('Melahirkan')
                                    ->content(fn($get, ?\App\Models\PengajuanCuti $record) => self::getSimulasiSaldo($get, $record)['melahirkan']),
                                Placeholder::make('sisa_alasan_penting')
                                    ->label('Alasan Penting')
                                    ->content(fn($get, ?\App\Models\PengajuanCuti $record) => self::getSimulasiSaldo($get, $record)['penting']),
                            ])
                            ->gridContainer(),

                        Textarea::make('alamat_selama_cuti')
                            ->label('Alamat Selama Cuti')
                            ->required(),
                            
                        \Filament\Schemas\Components\Fieldset::make('Catatan / Alasan dari Atasan')
                            ->schema([
                                Textarea::make('alasan_kanit')
                                    ->label('Catatan Kanit')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($record) => $record && $record->alasan_kanit !== null),
                                Textarea::make('alasan_kasubag')
                                    ->label('Catatan Kasubag')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($record) => $record && $record->alasan_kasubag !== null),
                                Textarea::make('alasan_pejabat')
                                    ->label('Catatan Pejabat')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($record) => $record && $record->alasan_pejabat !== null),
                            ])
                            ->visible(fn ($record) => $record && ($record->alasan_kanit !== null || $record->alasan_kasubag !== null || $record->alasan_pejabat !== null))
                            ->columnSpanFull(),
                    ])->columns(2),

            ]);
    }

    public static function kalkulasiLamaCuti($get, $set, $record = null)
    {
        $start = $get('tanggal_mulai');
        $end = $get('tanggal_selesai');

        if ($start && $end) {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);

            $owner = $record ? $record->user : Auth::user();
            $unitKerja = $owner->unitKerja;
            $kelompokId = $get('kelompok_kerja_id');
            $kelompokKerja = $kelompokId ? KelompokKerja::find($kelompokId) : null;

            $lama = CutiService::hitungLamaCuti($startDate, $endDate, $unitKerja, $kelompokKerja);
            $set('lama_cuti', $lama);
        } else {
            $set('lama_cuti', 0);
        }
    }

    private static function invalidDateSummary($get, $record = null): string
    {
        $start = $get('tanggal_mulai');
        $end = $get('tanggal_selesai');

        if (!$start || !$end) {
            return 'Pilih tanggal mulai dan selesai.';
        }

        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        if ($endDate->lt($startDate)) {
            return 'Tanggal selesai tidak valid.';
        }

        $owner = $record ? $record->user : Auth::user();
        $unitKerja = $owner->unitKerja;
        $kelompokId = $get('kelompok_kerja_id');
        $kelompokKerja = $kelompokId ? KelompokKerja::find($kelompokId) : null;

        try {
            $invalidDates = CutiService::invalidDates(
                $startDate,
                $endDate,
                $unitKerja,
                $kelompokKerja
            );
        } catch (\Throwable $exception) {
            return $exception->getMessage();
        }

        if ($invalidDates === []) {
            return 'Tidak ada hari libur dalam rentang ini.';
        }

        return implode(', ', $invalidDates);
    }

    public static function getSimulasiSaldo($get, $record = null): array
    {
        $targetUser = $record ? $record->user : Auth::user();
        $saldo = $targetUser->fresh()->saldoCuti;
        $default = ['n2' => '-', 'n1' => '-', 'n' => '-', 'besar' => '-', 'sakit' => '-', 'melahirkan' => '-', 'penting' => '-'];

        if (!$saldo) return $default;

        $lama = (int) $get('lama_cuti');
        $jenis = $get('jenis_cuti');

        if (!$jenis || $jenis === 'diluar_tanggungan_negara') {
            return [
                'n2' => ($saldo->saldo_n2 ?? 0) . ' hari',
                'n1' => ($saldo->saldo_n1 ?? 0) . ' hari',
                'n' => ($saldo->saldo_n ?? 0) . ' hari',
                'besar' => ($saldo->saldo_cuti_besar ?? 0) . ' hari',
                'sakit' => ($saldo->saldo_cuti_sakit ?? 0) . ' hari',
                'melahirkan' => ($saldo->saldo_cuti_melahirkan ?? 0) . ' hari',
                'penting' => ($saldo->saldo_cuti_alasan_penting ?? 0) . ' hari',
            ];
        }

        $n2 = $saldo->saldo_n2;
        $n1 = $saldo->saldo_n1;
        $n = $saldo->saldo_n;
        $besar = $saldo->saldo_cuti_besar;
        $sakit = $saldo->saldo_cuti_sakit;
        $melahirkan = $saldo->saldo_cuti_melahirkan;
        $penting = $saldo->saldo_cuti_alasan_penting;
        $isSimulated = false;

        if ($record && $record->status === 'disetujui' && $lama !== $record->getOriginal('lama_cuti')) {
            $dryRunResult = \App\Services\CutiService::koreksiSaldo($record, $record->getOriginal('lama_cuti'), $lama, true);
            if (isset($dryRunResult['error'])) {
                return ['n2' => 'Error: ' . $dryRunResult['error'], 'n1' => '-', 'n' => '-', 'besar' => '-', 'sakit' => '-', 'melahirkan' => '-', 'penting' => '-'];
            }
            if ($dryRunResult) {
                $n2 = $dryRunResult['n2'];
                $n1 = $dryRunResult['n1'];
                $n = $dryRunResult['n'];
                $besar = $dryRunResult['besar'];
                $sakit = $dryRunResult['sakit'];
                $melahirkan = $dryRunResult['melahirkan'];
                $penting = $dryRunResult['penting'];
                $isSimulated = true;
            }
        }

        $activeHolds = \App\Services\CutiService::getActiveHoldsByJenis($targetUser, $jenis);
        $isPending = $record && in_array($record->status, ['menunggu_atasan', 'menunggu_pejabat', 'disetujui_sementara_kanit']);
        if ($isPending && $record->jenis_cuti === $jenis) {
            $activeHolds -= $record->getOriginal('lama_cuti');
        }
        $activeHolds = max(0, $activeHolds);

        if ($activeHolds > 0 && $jenis === 'tahunan') {
            if ($n2 >= $activeHolds) { $n2 -= $activeHolds; $activeHolds = 0; }
            else { $activeHolds -= $n2; $n2 = 0; }
            
            if ($activeHolds > 0) {
                if ($n1 >= $activeHolds) { $n1 -= $activeHolds; $activeHolds = 0; }
                else { $activeHolds -= $n1; $n1 = 0; }
            }
            if ($activeHolds > 0) { $n = max(0, $n - $activeHolds); }
        } elseif ($activeHolds > 0) {
            switch ($jenis) {
                case 'besar': $besar = max(0, $besar - $activeHolds); break;
                case 'sakit': $sakit = max(0, $sakit - $activeHolds); break;
                case 'melahirkan': $melahirkan = max(0, $melahirkan - $activeHolds); break;
                case 'alasan_penting': $penting = max(0, $penting - $activeHolds); break;
            }
        }

        if (!$record || $record->status !== 'disetujui') {
            $isSimulated = true;
            if ($jenis === 'tahunan') {
                $sisaPotong = $lama;
                if ($n2 >= $sisaPotong) { $n2 -= $sisaPotong; $sisaPotong = 0; }
                else { $sisaPotong -= $n2; $n2 = 0; }
                
                if ($sisaPotong > 0) {
                    if ($n1 >= $sisaPotong) { $n1 -= $sisaPotong; $sisaPotong = 0; }
                    else { $sisaPotong -= $n1; $n1 = 0; }
                }
                if ($sisaPotong > 0) { $n -= $sisaPotong; }
            } else {
                switch ($jenis) {
                    case 'besar': $besar -= $lama; break;
                    case 'sakit': $sakit -= $lama; break;
                    case 'melahirkan': $melahirkan -= $lama; break;
                    case 'alasan_penting': $penting -= $lama; break;
                }
            }
        }

        $suffix = $isSimulated ? ' hari (Preview)' : ' hari';
        return [
            'n2' => $n2 . $suffix,
            'n1' => $n1 . $suffix,
            'n' => $n . $suffix,
            'besar' => $besar . $suffix,
            'sakit' => $sakit . $suffix,
            'melahirkan' => $melahirkan . $suffix,
            'penting' => $penting . $suffix,
        ];
    }
}
