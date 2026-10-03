<!DOCTYPE html>
<html>
<head>
    <title>Formulir Cuti</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7.5pt;
            color: #000;
            line-height: 1.1;
            margin: 0;
            padding: 0;
        }
        .page {
            position: relative;
            width: 210mm;
            height: 297mm;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* HEADER AREA */
        .header-container {
            position: absolute;
            top: 10mm; /* Moved up from 25mm */
            left: 115mm;
            width: 75mm;
        }

        .header-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-container td {
            padding: 2px 0;
            vertical-align: top;
            border: none;
            font-size: 7.5pt;
        }

        .header-container .label-yth {
            width: 10mm;
        }

        .judul-formulir {
            position: absolute;
            top: 37mm; /* Moved up from 52mm */
            left: 18.5mm;
            width: 173mm;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
        }

        /* FORM AREA */
        .form-container {
            position: absolute;
            left: 18.5mm;
            top: 45.5mm; /* Moved up from 60.5mm */
            width: 173mm;
        }

        .form-table, .form-table td, .form-table th, .form-table col {
            box-sizing: border-box;
        }
        table.form-table {
            width: 173mm;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .form-table td {
            border: 1px solid #000;
            padding: 1px 3px;
            vertical-align: middle;
            text-align: left;
            overflow: hidden;
            word-wrap: break-word;
        }
        .form-table td.tc { text-align: center; }
        .form-table td.top { vertical-align: top; }

        .section-title { 
            font-weight: normal; 
            background-color: #fff; 
            padding: 1.5px 3px; 
            height: 4mm;
        }
        
        .signature-img { 
            max-width: 22mm; 
            max-height: 12mm; 
            object-fit: contain;
            vertical-align: middle; 
        }

        .persetujuan-title { font-size: 7pt; padding: 1px; text-align: center; }
        
        /* Specific row heights */
        .row-data td { height: 5.5mm; }
        .row-jenis td { height: 4.8mm; }
        .row-alasan td { height: 13.5mm; }
        .row-lama td { height: 5.5mm; }
        .row-catatan td { height: 5mm; }
        
        .row-alamat-1 td { height: 5mm; }
        .row-alamat-2 td { height: 4mm; }
        .row-alamat-3 td { height: 16mm; vertical-align: bottom; padding-bottom: 2mm;}

        .row-persetujuan-header td { height: 4mm; }
        .row-persetujuan-sub td { height: 6mm; }
        .row-persetujuan-sig td { height: 18mm; vertical-align: bottom; padding-bottom: 2mm; text-align: center; }
        
        .row-keputusan-header td { height: 4mm; }
        .row-keputusan-sig td { height: 18mm; vertical-align: bottom; padding-bottom: 2mm; text-align: center; } /* Reduced from 25mm to 18mm */

        /* Gaps */
        .gap-section { height: 2.5mm; }


    </style>
</head>
<body>

@php
    \Carbon\Carbon::setLocale('id');

    if (!function_exists('getSignatureBase64')) {
        function getSignatureBase64($path) {
            if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                try {
                    $content = \Illuminate\Support\Facades\Storage::disk('public')->get($path);
                    $mime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($path);
                    return 'data:'.$mime.';base64,'.base64_encode($content);
                } catch (\Exception $e) {
                    return null;
                }
            }
            return null;
        }
    }

    // --- Bagian tujuan surat (Kepada Yth.) ---
    $pemohonAdalahPejabat = method_exists($pengajuanCuti->user, 'hasRole')
        && $pengajuanCuti->user->hasRole('pejabat_berwenang');

    $tujuanJabatan = $pemohonAdalahPejabat
        ? 'Sekretaris Direktorat Jenderal Perhubungan Udara'
        : 'Kepala Kantor BLU UPBU Mutiara Sis Al-Jufri';

    $tujuanKota = $pemohonAdalahPejabat ? 'Jakarta' : 'Palu';

    // --- Masa kerja (Tahun & Bulan) ---
    $masaKerja = '-';
    if ($pengajuanCuti->user->tanggal_masuk) {
        $tglMasuk = is_string($pengajuanCuti->user->tanggal_masuk) 
            ? \Carbon\Carbon::parse($pengajuanCuti->user->tanggal_masuk) 
            : $pengajuanCuti->user->tanggal_masuk;
        $diffMasaKerja = $tglMasuk->diff(now());
        $masaKerja = $diffMasaKerja->y . ' Tahun ' . $diffMasaKerja->m . ' Bulan';
    }

    // --- Tanda jenis cuti ---
    $tandaJenis = fn ($tipe) => $pengajuanCuti->jenis_cuti === $tipe ? '&#10003;' : '-';

    // --- Saldo cuti (N-2, N-1, N) ---
    $tahunN  = $pengajuanCuti->user->saldoCuti?->tahun_berjalan ?? now()->year;
    $tahunN1 = $tahunN - 1;
    $tahunN2 = $tahunN - 2;

    $saldoN  = $pengajuanCuti->user->saldoCuti?->saldo_n ?? 0;
    $saldoN1 = $pengajuanCuti->user->saldoCuti?->saldo_n1 ?? 0;
    $saldoN2 = $pengajuanCuti->user->saldoCuti?->saldo_n2 ?? 0;

    $fmtSaldo = fn ($nilai) => $nilai > 0 ? $nilai . ' Hari' : '-';

    $sisaArr = [];
    if ($saldoN2 > 0) {
        $sisaArr[] = "Tahun {$tahunN2} {$saldoN2} Hari";
    }
    if ($saldoN1 > 0) {
        $sisaArr[] = "Tahun {$tahunN1} {$saldoN1} Hari";
    }
    if ($saldoN > 0) {
        $sisaArr[] = "Tahun {$tahunN} {$saldoN} Hari";
    }

    if (empty($sisaArr)) {
        $teksSisaCuti = "Tahun {$tahunN1} 0 Hari, {$tahunN} 0 Hari";
    } else {
        $teksSisaCuti = implode(', ', $sisaArr);
    }

    // --- Keputusan atasan langsung & pejabat ---
    $kanitApproved = strtolower($pengajuanCuti->keputusan_kanit ?? '') === 'disetujui';
    $kanitRejected = !empty($pengajuanCuti->keputusan_kanit) && !in_array(strtolower($pengajuanCuti->keputusan_kanit), ['disetujui', 'dilewati']);

    $kasubagApproved = strtolower($pengajuanCuti->keputusan_kasubag ?? '') === 'disetujui';
    $kasubagRejected = !empty($pengajuanCuti->keputusan_kasubag) && !in_array(strtolower($pengajuanCuti->keputusan_kasubag), ['disetujui', 'dilewati']);

    $isOperasional = ($pengajuanCuti->tipe_aliran ?? 'administrasi') === 'operasional';
    $isAdministrasi = !$isOperasional;
    $isAdministrasiKanitIsKepegawaian = ($isAdministrasi && $pengajuanCuti->kanit && $pengajuanCuti->kanit->hasRole('kanit_kepegawaian'));

    $koordinatorApprove = $isOperasional ? ($pengajuanCuti->keputusan_kepala_unit === 'disetujui') : ($isAdministrasiKanitIsKepegawaian ? false : $kanitApproved);
    $koordinatorReject = $isOperasional ? (in_array($pengajuanCuti->keputusan_kepala_unit, ['ditolak_kepala_unit', 'ditolak', 'perubahan'])) : ($isAdministrasiKanitIsKepegawaian ? false : $kanitRejected);
    $koordinatorApprover = $isOperasional ? $pengajuanCuti->kepalaUnit : ($isAdministrasiKanitIsKepegawaian ? null : $pengajuanCuti->kanit);

    $kasiApprove = $isOperasional ? ($pengajuanCuti->keputusan_kepala_seksi === 'disetujui') : false;
    $kasiReject = $isOperasional ? (in_array($pengajuanCuti->keputusan_kepala_seksi, ['ditolak_kepala_seksi', 'ditolak', 'perubahan'])) : false;
    $kasiApprover = $isOperasional ? $pengajuanCuti->kepalaSeksi : null;

    $kanitPegApprove = $isOperasional ? ($pengajuanCuti->keputusan_kanit_kepegawaian === 'disetujui') : ($isAdministrasiKanitIsKepegawaian ? $kanitApproved : ($pengajuanCuti->keputusan_kanit_kepegawaian === 'disetujui'));
    $kanitPegReject = $isOperasional ? (in_array($pengajuanCuti->keputusan_kanit_kepegawaian, ['ditolak_kanit_kepegawaian', 'ditolak', 'perubahan'])) : ($isAdministrasiKanitIsKepegawaian ? $kanitRejected : in_array($pengajuanCuti->keputusan_kanit_kepegawaian, ['ditolak_kanit_kepegawaian', 'ditolak', 'perubahan'])); 
    $kanitPegApprover = $isOperasional ? $pengajuanCuti->kanitKepegawaian : ($isAdministrasiKanitIsKepegawaian ? $pengajuanCuti->kanit : $pengajuanCuti->kanitKepegawaian);

    $kasubagApprove = $isOperasional ? ($pengajuanCuti->keputusan_kasubag_tu === 'disetujui') : ($kasubagApproved);
    $kasubagReject = $isOperasional ? (in_array($pengajuanCuti->keputusan_kasubag_tu, ['ditolak_kasubag_tu', 'ditolak', 'perubahan'])) : ($kasubagRejected);
    $kasubagApprover = $isOperasional ? $pengajuanCuti->kasubagTu : $pengajuanCuti->kasubag;

    $formatBox = function ($approveStatus, $approver, $canSign = true) {
        if (!$approveStatus) return '';

        $out = '';
        $sig = $canSign && $approver ? getSignatureBase64($approver->signature_path) : null;
        if ($sig) {
            $out .= "<img src=\"{$sig}\" class=\"signature-img\"><br>";
        } elseif ($approver) {
            $out .= '<br>';
        }
        if ($approver) {
            $out .= "<u>{$approver->nama}</u>";
        }
        return $out;
    };
@endphp

<div class="page">
    {{-- HEADER --}}
    <div class="header-container">
        <table>
            <tr><td style="white-space: nowrap;">Palu, {{ $pengajuanCuti->created_at?->translatedFormat('d F Y') ?? '' }}</td></tr>
            <tr><td>Kepada</td></tr>
            <tr>
                <td class="label-yth">Yth. {{ $tujuanJabatan }}</td>
                <!-- <td>{{ $tujuanJabatan }}</td> -->
            </tr>
            <tr><td>di</td></tr>
            <tr><td class="kota-tujuan">{{ $tujuanKota }}</td></tr>
        </table>
    </div>

    <div class="judul-formulir">FORMULIR PERMINTAAN DAN PEMBERIAN CUTI</div>

    {{-- FORM CONTAINER --}}
    <div class="form-container">
        
        {{-- I. DATA PEGAWAI --}}
        <table class="form-table">
            <colgroup>
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
            </colgroup>
            <tr><td colspan="7" class="section-title">I. DATA PEGAWAI</td></tr>
            <tr class="row-data">
                <td>Nama</td>
                <td colspan="3">{{ $pengajuanCuti->user->nama ?? '' }}</td>
                <td>NIP.</td>
                <td colspan="2">{{ $pengajuanCuti->user->nip ?? '' }}</td>
            </tr>
            <tr class="row-data">
                <td>Jabatan</td>
                <td colspan="3">{{ $pengajuanCuti->user->jabatan ?? '' }}</td>
                <td>Pangkat /Gol.</td>
                <td colspan="2">{{ $pengajuanCuti->user->pangkat_golongan ?? '-' }}</td>
            </tr>
            <tr class="row-data">
                <td>Unit Kerja</td>
                <td colspan="3">{{ $pengajuanCuti->user->unitKerja?->nama_unit ?? '' }}</td>
                <td>Masa Kerja</td>
                <td colspan="2">{{ $masaKerja }}</td>
            </tr>
        </table>

        <div class="gap-section"></div>

        {{-- II. JENIS CUTI YANG DIAMBIL --}}
        <table class="form-table">
            <colgroup>
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
            </colgroup>
            <tr><td colspan="7" class="section-title">II. JENIS CUTI YANG DIAMBIL</td></tr>
            <tr class="row-jenis">
                <td colspan="2">1. Cuti Tahunan / Bersama</td>
                <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('tahunan') !!}</span></td>
                <td colspan="3">2. Cuti Besar</td>
                <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('besar') !!}</span></td>
            </tr>
            <tr class="row-jenis">
                <td colspan="2">3. Cuti Sakit</td>
                <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('sakit') !!}</span></td>
                <td colspan="3">4. Cuti Melahirkan</td>
                <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('melahirkan') !!}</span></td>
            </tr>
            <tr class="row-jenis">
                <td colspan="2">5. Cuti Karena Alasan Penting</td>
                <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('alasan_penting') !!}</span></td>
                <td colspan="3">6. Cuti di luar Tanggungan Negara</td>
                <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('diluar_tanggungan_negara') !!}</span></td>
            </tr>
        </table>

        <div class="gap-section"></div>

        {{-- III. ALASAN CUTI --}}
        <table class="form-table">
            <colgroup>
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
            </colgroup>
            <tr><td colspan="7" class="section-title">III. ALASAN CUTI</td></tr>
            <tr class="row-alasan"><td colspan="7" class="top">{{ $pengajuanCuti->alasan_cuti ?? '' }}</td></tr>
        </table>

        <div class="gap-section"></div>

        {{-- IV. LAMANYA CUTI --}}
        <table class="form-table">
            <colgroup>
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
            </colgroup>
            <tr><td colspan="7" class="section-title">IV. LAMANYA CUTI</td></tr>
            <tr class="row-lama">
                <td>Selama</td>
                <td colspan="2" class="tc">{{ $pengajuanCuti->lama_cuti ?? '' }} Hari</td>
                <td class="tc">Mulai Tanggal</td>
                <td colspan="3" class="tc">{{ $pengajuanCuti->tanggal_mulai?->translatedFormat('d F Y') ?? '' }} s/d {{ $pengajuanCuti->tanggal_selesai?->translatedFormat('d F Y') ?? '' }}</td>
            </tr>
        </table>

        <div class="gap-section"></div>

        {{-- V. CATATAN CUTI --}}
        <table class="form-table">
            <colgroup>
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
            </colgroup>
            <tr><td colspan="7" class="section-title">V. CATATAN CUTI</td></tr>
            <tr class="row-catatan">
                <td colspan="3">1. CUTI TAHUNAN</td>
                <td colspan="3">2. CUTI BESAR</td>
                <td class="tc">{{ $fmtSaldo($pengajuanCuti->user->saldoCuti?->saldo_cuti_besar ?? 0) }}</td>
            </tr>
            <tr class="row-catatan">
                <td colspan="2">Tahun :</td>
                <td class="tc">Keterangan</td>
                <td colspan="3">3. CUTI SAKIT</td>
                <td class="tc">{{ $fmtSaldo($pengajuanCuti->user->saldoCuti?->saldo_cuti_sakit ?? 0) }}</td>
            </tr>
            <tr class="row-catatan">
                <td colspan="2">N-2 : {{ $tahunN2 }}</td>
                <td class="tc">{{ $fmtSaldo($saldoN2) }}</td>
                <td colspan="3">4. CUTI MELAHIRKAN</td>
                <td class="tc">{{ $fmtSaldo($pengajuanCuti->user->saldoCuti?->saldo_cuti_melahirkan ?? 0) }}</td>
            </tr>
            <tr class="row-catatan">
                <td colspan="2">N-1 : {{ $tahunN1 }}</td>
                <td class="tc">{{ $fmtSaldo($saldoN1) }}</td>
                <td colspan="3">5. CUTI KARENA ALASAN PENTING</td>
                <td class="tc">{{ $fmtSaldo($pengajuanCuti->user->saldoCuti?->saldo_cuti_alasan_penting ?? 0) }}</td>
            </tr>
            <tr class="row-catatan">
                <td colspan="2">N : {{ $tahunN }}</td>
                <td class="tc">{{ $fmtSaldo($saldoN) }}</td>
                <td colspan="3">6. CUTI DILUAR TANGGUNGAN NEGARA</td>
                <td class="tc">-</td>
            </tr>
            <tr class="row-catatan">
                <td colspan="7">Sisa Cuti : {{ $teksSisaCuti }}</td>
            </tr>
        </table>

        <div class="gap-section"></div>

        {{-- VI. ALAMAT SELAMA MENJALANKAN CUTI --}}
        <table class="form-table">
            <colgroup>
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
            </colgroup>
            <tr><td colspan="7" class="section-title">VI. ALAMAT SELAMA MENJALANKAN CUTI</td></tr>
            <tr class="row-alamat-1">
                <td rowspan="3" colspan="4" class="top">{{ $pengajuanCuti->alamat_selama_cuti ?? '' }}</td>
                <td>TELP / HP</td>
                <td colspan="2">{{ $pengajuanCuti->user->nomor_telp ?? '-' }}</td>
            </tr>
            <tr class="row-alamat-2">
                <td colspan="3" class="tc" style="border-bottom: none;">Hormat Saya,</td>
            </tr>
            <tr class="row-alamat-3">
                <td colspan="3" class="tc" style="border-top: none;">
                    @if(isset($pengajuanCuti->user) && $sig = getSignatureBase64($pengajuanCuti->user->signature_path))
                        <img src="{{ $sig }}" class="signature-img"><br>
                    @endif
                    <u>{{ $pengajuanCuti->user->nama ?? '' }}</u><br>
                    NIP. {{ $pengajuanCuti->user->nip ?? '' }}
                </td>
            </tr>
        </table>

        <div class="gap-section"></div>

        {{-- VII. PERTIMBANGAN ATASAN LANGSUNG --}}
        <table class="form-table">
            <colgroup>
                <col style="width:24.71mm">
                <col style="width:24.71mm">
                <col style="width:24.71mm">
                <col style="width:24.71mm">
                <col style="width:24.71mm">
                <col style="width:24.71mm">
                <col style="width:24.74mm">
            </colgroup>
            <tr><td colspan="7" class="section-title">VII. PERTIMBANGAN ATASAN LANGSUNG</td></tr>
            <tr class="row-persetujuan-header">
                <td colspan="4" class="tc persetujuan-title">DISETUJUI</td>
                <td colspan="3" class="tc persetujuan-title">DITANGGUHKAN / TIDAK DISETUJUI</td>
            </tr>
            <tr class="row-persetujuan-sub">
                <td class="tc persetujuan-title">KOORDINATOR/<br>KANIT</td>
                <td class="tc persetujuan-title">KASI</td>
                <td class="tc persetujuan-title">KOORDINATOR<br>KEPEGAWAIAN</td>
                <td class="tc persetujuan-title">KASUBAG TU</td>
                <td class="tc persetujuan-title">KOORDINATOR/<br>KANIT</td>
                <td class="tc persetujuan-title">KASI</td>
                <td class="tc persetujuan-title">KASUBAG TU</td>
            </tr>
            <tr class="row-persetujuan-sig">
                <td>{!! $formatBox($koordinatorApprove, $koordinatorApprover) !!}</td>
                <td>{!! $formatBox($kasiApprove, $kasiApprover) !!}</td>
                <td>{!! $formatBox($kanitPegApprove, $kanitPegApprover) !!}</td>
                <td>{!! $formatBox($kasubagApprove, $kasubagApprover) !!}</td>
                <td>{!! $formatBox($koordinatorReject, $koordinatorApprover) !!}</td>
                <td>{!! $formatBox($kasiReject, $kasiApprover) !!}</td>
                <td>{!! $formatBox($kasubagReject, $kasubagApprover) !!}</td>
            </tr>
        </table>

        <div class="gap-section"></div>

        {{-- VIII. KEPUTUSAN PEJABAT YANG BERWENANG --}}
        @php
            $blangko = $pengajuanCuti->blangkoCuti ?? null;
            $kabandaraApproved = $blangko && strtolower($blangko->status) === 'disetujui';
            $kabandaraRejected = $blangko && in_array(strtolower($blangko->status), ['ditolak', 'perubahan', 'ditangguhkan']);
            
            $kabandaraNama = $blangko ? $blangko->kabandara_nama : null;
            if (!$kabandaraNama && $blangko && $blangko->kabandara) {
                $kabandaraNama = $blangko->kabandara->nama;
            }
            
            $signaturePath = ($blangko && $blangko->kabandara) ? $blangko->kabandara->signature_path : null;
        @endphp
        <table class="form-table">
            <colgroup>
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
                <col style="width:14.2857%">
            </colgroup>
            <tr><td colspan="7" class="section-title">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI</td></tr>
            <tr class="row-keputusan-header">
                <td colspan="4" class="tc persetujuan-title">DISETUJUI</td>
                <td colspan="3" class="tc persetujuan-title">DITANGGUHKAN / TIDAK DISETUJUI</td>
            </tr>
            <tr class="row-keputusan-sig">
                <td colspan="4">
                    @if($kabandaraApproved)
                        @if($signaturePath && $sig = getSignatureBase64($signaturePath))
                            <img src="{{ $sig }}" class="signature-img"><br>
                        @endif
                        <u>{{ $kabandaraNama ?? '...................' }}</u>
                    @endif
                </td>
                <td colspan="3">
                    @if($kabandaraRejected)
                        @if($signaturePath && $sig = getSignatureBase64($signaturePath))
                            <img src="{{ $sig }}" class="signature-img"><br>
                        @endif
                        <u>{{ $kabandaraNama ?? '...................' }}</u>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
