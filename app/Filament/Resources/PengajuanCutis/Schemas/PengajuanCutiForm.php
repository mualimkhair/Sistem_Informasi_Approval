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
                            ->default(fn () => Auth::user()->nama)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('nip_display')
                            ->label('NIP')
                            ->default(fn () => Auth::user()->nip)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('jabatan_display')
                            ->label('Jabatan')
                            ->default(fn () => Auth::user()->jabatan)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('unit_kerja_display')
                            ->label('Unit Kerja')
                            ->default(fn () => Auth::user()->unitKerja->nama_unit ?? '-')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('masa_kerja_display')
                            ->label('Masa Kerja')
                            ->default(fn () => Auth::user()->tanggal_masuk ? Auth::user()->tanggal_masuk->diffInYears(now()) . ' Tahun' : '0 Tahun')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(3),

                Section::make('Detail Pengajuan')
                    ->schema([
                        Hidden::make('user_id')->default(fn () => Auth::id()),
                        
                        Select::make('kelompok_kerja_id')
                            ->label('Kelompok Kerja')
                            ->options(function () {
                                if (Auth::user()->unitKerja?->jenis === 'operasional') {
                                    return KelompokKerja::where('unit_kerja_id', Auth::user()->unit_kerja_id)->pluck('nama_kelompok', 'id');
                                }
                                return [];
                            })
                            ->visible(fn () => Auth::user()->unitKerja?->jenis === 'operasional')
                            ->required(fn () => Auth::user()->unitKerja?->jenis === 'operasional')
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
                            ->required()
                            ->live(),

                        Textarea::make('alasan_cuti')
                            ->label('Alasan Cuti')
                            ->required(),

                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->live()
                            ->rules([
                                fn ($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
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
                            ->afterOrEqual('tanggal_mulai')
                            ->live()
                            ->rules([
                                fn ($get) => function (string $attribute, $value, \Closure $fail) use ($get) {
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
                            ->content(fn (Get $get): string => self::invalidDateSummary($get))
                            ->columnSpanFull(),

                        Placeholder::make('sisa_saldo')
                            ->label('Catatan Cuti (Saldo Tahunan)')
                            ->content(function ($get) {
                                $saldo = Auth::user()->saldoCuti;
                                if (!$saldo) return 'Saldo tidak ditemukan';
                                
                                $lama = (int) $get('lama_cuti');
                                $jenis = $get('jenis_cuti');
                                
                                if ($jenis !== 'tahunan' || $lama <= 0) {
                                    return "N: {$saldo->saldo_n} | N-1: {$saldo->saldo_n1} | N-2: {$saldo->saldo_n2}";
                                }
                                
                                $n2 = $saldo->saldo_n2;
                                $n1 = $saldo->saldo_n1;
                                $n = $saldo->saldo_n;
                                
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
                                
                                return "Sisa Saldo Setelah Cuti -> N: {$n} | N-1: {$n1} | N-2: {$n2}";
                            }),

                        Textarea::make('alamat_selama_cuti')
                            ->label('Alamat Selama Cuti')
                            ->required(),
                    ])->columns(2),

            ]);
    }

    public static function kalkulasiLamaCuti(Get $get, Set $set)
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

    private static function invalidDateSummary(Get $get): string
    {
        $start = $get('tanggal_mulai');
        $end = $get('tanggal_selesai');

        if (! $start || ! $end) {
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
}
