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
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('nip_display')
                            ->label('NIP')
                            ->default(fn() => Auth::user()->nip)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('jabatan_display')
                            ->label('Jabatan')
                            ->default(fn() => Auth::user()->jabatan)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('unit_kerja_display')
                            ->label('Unit Kerja')
                            ->default(fn() => Auth::user()->unitKerja->nama_unit ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('masa_kerja_display')
                            ->label('Masa Kerja')
                            ->default(fn() => Auth::user()->tanggal_masuk ? Auth::user()->tanggal_masuk->diffInYears(now()) . ' Tahun' : '0 Tahun')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(3),

                Section::make('Detail Pengajuan')
                    ->schema([
                        Hidden::make('user_id')->default(fn() => Auth::id()),

                        Select::make('kelompok_kerja_id')
                            ->label('Kelompok Kerja')
                            ->options(function () {
                                if (Auth::user()->unitKerja?->jenis === 'operasional') {
                                    return KelompokKerja::where('unit_kerja_id', Auth::user()->unit_kerja_id)->pluck('nama_kelompok', 'id');
                                }
                                return [];
                            })
                            ->searchable()
                            ->visible(fn() => Auth::user()->unitKerja?->jenis === 'operasional')
                            ->required(fn() => Auth::user()->unitKerja?->jenis === 'operasional')
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
                            ->live(),

                        Textarea::make('alasan_cuti')
                            ->label('Alasan Cuti')
                            ->required(),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->minDate(today())
                            ->live()
                            ->rules([
                                fn($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $date = Carbon::parse($value);
                                    $unitKerja = Auth::user()->unitKerja;
                                    $kelompokKerja = $get('kelompok_kerja_id') ? KelompokKerja::find($get('kelompok_kerja_id')) : null;
                                    if (\App\Services\CutiService::hitungLamaCuti($date, $date, $unitKerja, $kelompokKerja) === 0) {
                                        $fail('Tanggal tidak boleh jatuh pada hari libur / jadwal libur Anda.');
                                    }
                                },
                            ])
                            ->afterStateUpdated(function ($state, $get, $set) {
                                self::kalkulasiLamaCuti($get, $set);
                            }),

                        DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->minDate(today())
                            ->afterOrEqual('tanggal_mulai')
                            ->live()
                            ->rules([
                                fn($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $date = Carbon::parse($value);
                                    $unitKerja = Auth::user()->unitKerja;
                                    $kelompokKerja = $get('kelompok_kerja_id') ? KelompokKerja::find($get('kelompok_kerja_id')) : null;
                                    if (\App\Services\CutiService::hitungLamaCuti($date, $date, $unitKerja, $kelompokKerja) === 0) {
                                        $fail('Tanggal tidak boleh jatuh pada hari libur / jadwal libur Anda.');
                                    }
                                },
                            ])
                            ->afterStateUpdated(function ($state, $get, $set) {
                                self::kalkulasiLamaCuti($get, $set);
                            }),

                        TextInput::make('lama_cuti')
                            ->label('Lama Cuti (Hari Kerja)')
                            ->numeric()
                            ->readOnly()
                            ->required(),

                        Placeholder::make('tanggal_alert')
                            ->label('Tanggal Libur Dikecualikan')
                            ->content(fn($get): string => self::invalidDateSummary($get))
                            ->columnSpanFull(),

                        \Filament\Schemas\Components\Fieldset::make('Informasi Sisa Saldo Cuti')
                            ->schema([
                                Placeholder::make('sisa_n2')
                                    ->label('Saldo N-2')
                                    ->content(fn($get) => self::getSimulasiSaldo($get)['n2']),
                                Placeholder::make('sisa_n1')
                                    ->label('Saldo N-1')
                                    ->content(fn($get) => self::getSimulasiSaldo($get)['n1']),
                                Placeholder::make('sisa_n')
                                    ->label('Saldo N')
                                    ->content(fn($get) => self::getSimulasiSaldo($get)['n']),
                                Placeholder::make('sisa_besar')
                                    ->label('Cuti Besar')
                                    ->content(fn($get) => self::getSimulasiSaldo($get)['besar']),
                                Placeholder::make('sisa_sakit')
                                    ->label('Cuti Sakit')
                                    ->content(fn($get) => self::getSimulasiSaldo($get)['sakit']),
                                Placeholder::make('sisa_melahirkan')
                                    ->label('Melahirkan')
                                    ->content(fn($get) => self::getSimulasiSaldo($get)['melahirkan']),
                                Placeholder::make('sisa_alasan_penting')
                                    ->label('Alasan Penting')
                                    ->content(fn($get) => self::getSimulasiSaldo($get)['penting']),
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

    public static function kalkulasiLamaCuti($get, $set)
    {
        $start = $get('tanggal_mulai');
        $end = $get('tanggal_selesai');

        if ($start && $end) {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);

            $unitKerja = Auth::user()->unitKerja;
            $kelompokId = $get('kelompok_kerja_id');
            $kelompokKerja = $kelompokId ? KelompokKerja::find($kelompokId) : null;

            $lama = CutiService::hitungLamaCuti($startDate, $endDate, $unitKerja, $kelompokKerja);
            $set('lama_cuti', $lama);
        } else {
            $set('lama_cuti', 0);
        }
    }

    private static function invalidDateSummary($get): string
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

        $unitKerja = Auth::user()->unitKerja;
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

    public static function getSimulasiSaldo($get): array
    {
        $saldo = Auth::user()->fresh()->saldoCuti;
        $default = ['n2' => '-', 'n1' => '-', 'n' => '-', 'besar' => '-', 'sakit' => '-', 'melahirkan' => '-', 'penting' => '-'];

        $lama = (int) $get('lama_cuti');
        $jenis = $get('jenis_cuti');

        if (!$jenis || $jenis === 'diluar_tanggungan_negara') {
            return [
                'n2' => $saldo->saldo_n2 . ' hari',
                'n1' => $saldo->saldo_n1 . ' hari',
                'n' => $saldo->saldo_n . ' hari',
                'besar' => $saldo->saldo_cuti_besar . ' hari',
                'sakit' => $saldo->saldo_cuti_sakit . ' hari',
                'melahirkan' => $saldo->saldo_cuti_melahirkan . ' hari',
                'penting' => $saldo->saldo_cuti_alasan_penting . ' hari',
            ];
        }

        if (!$saldo)
            return $default;

        //get base values from saldo_cutis
        $n2 = $saldo->saldo_n2;
        $n1 = $saldo->saldo_n1;
        $n = $saldo->saldo_n;
        $besar = $saldo->saldo_cuti_besar;
        $sakit = $saldo->saldo_cuti_sakit;
        $melahirkan = $saldo->saldo_cuti_melahirkan;
        $penting = $saldo->saldo_cuti_alasan_penting;



        if ($jenis !== 'diluar_tanggungan_negara') {
            $user = Auth::user();
            $activeHolds = CutiService::getActiveHoldsByJenis($user, $jenis);

            if ($activeHolds > 0 && $jenis === 'tahunan') {
                if ($n2 >= $activeHolds) {
                    $n2 -= $activeHolds;
                    $activeHolds = 0;
                } else {
                    $activeHolds -= $n2;
                    $n2 = 0;
                }
                if ($activeHolds > 0) {
                    if ($n1 >= $activeHolds) {
                        $n1 -= $activeHolds;
                        $activeHolds = 0;
                    } else {
                        $activeHolds -= $n1;
                        $n1 = 0;
                    }
                }
                if ($activeHolds > 0) {
                    $n = max(0, $n - $activeHolds);
                }
            } elseif ($activeHolds > 0 && in_array($jenis, ['besar', 'sakit', 'melahirkan', 'alasan_penting'])) {
                switch ($jenis) {
                    case 'besar':
                        $besar = max(0, $besar - $activeHolds);
                        break;
                    case 'sakit':
                        $sakit = max(0, $sakit - $activeHolds);
                        break;
                    case 'melahirkan':
                        $melahirkan = max(0, $melahirkan - $activeHolds);
                        break;
                    case 'alasan_penting':
                        $penting = max(0, $penting - $activeHolds);
                        break;
                }
            }
        }


        if ($lama > 0) {
            switch ($jenis) {
                case 'tahunan':
                    if ($n2 >= $lama) {
                        $n2 -= $lama;
                        $lama = 0;
                    } else {
                        $lama -= $n2;
                        $n2 = 0;
                    }

                    if ($lama > 0) {
                        if ($n1 >= $lama) {
                            $n1 -= $lama;
                            $lama = 0;
                        } else {
                            $lama -= $n1;
                            $n1 = 0;
                        }
                    }
                    if ($lama > 0) {
                        $n -= $lama;
                    }
                    break;
                case 'besar':
                    $besar -= $lama;
                    break;
                case 'sakit':
                    $sakit -= $lama;
                    break;
                case 'melahirkan':
                    $melahirkan -= $lama;
                    break;
                case 'alasan_penting':
                    $penting -= $lama;
                    break;
            }
        }

        return [
            'n2' => $n2 . ' hari',
            'n1' => $n1 . ' hari',
            'n' => $n . ' hari',
            'besar' => $besar . ' hari',
            'sakit' => $sakit . ' hari',
            'melahirkan' => $melahirkan . ' hari',
            'penting' => $penting . ' hari',
        ];
    }
}
