<!DOCTYPE html>
<html>
<head>
    <title>Formulir Cuti</title>
    <style>
        @page {
            /* margin: 1in 0.45in 1in 0.7in; */
            margin: 0.7in 0.3in 0.7in 0.3in;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.25;
        }
        .surat-header {
            width: 46%;
            margin-left: 54%;
            margin-bottom: 2px;
        }
        .surat-header table { width: 100%; }
        .surat-header td {
            border: none;
            padding: 1px 0;
            vertical-align: top;
        }
        .surat-header .label-yth {
            width: 14%;
            text-align: right;
            padding-right: 4px;
        }
        .surat-header .kota-tujuan { padding-left: 20px; }

        .judul-formulir {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
        }

        table.form-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .form-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: middle;
            text-align: left;
        }
        .form-table td.tc { text-align: center; }
        .form-table td.top { vertical-align: top; }

        .section-title { font-weight: normal; }
        .tall-box { height: 72px; }
        .tall-box-lg { height: 118px; }

        .signature-img { max-width: 75px; max-height: 42px; vertical-align: middle; }

        .persetujuan-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .persetujuan-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
            text-align: center;
            word-wrap: break-word;
        }
        .persetujuan-title { font-weight: bold; }
        .persetujuan-table td.approval-cell { height: 60px; padding: 15px 4px 4px; }
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
    $tahunN  = now()->year;
    $tahunN1 = $tahunN - 1;
    $tahunN2 = $tahunN - 2;

    $saldoN  = $pengajuanCuti->user->saldoCuti?->saldo_n ?? 0;
    $saldoN1 = $pengajuanCuti->user->saldoCuti?->saldo_n1 ?? 0;
    $saldoN2 = $pengajuanCuti->user->saldoCuti?->saldo_n2 ?? 0;

    $fmtSaldo = fn ($nilai) => $nilai > 0 ? $nilai . ' Hari' : '-';

    // Sisa cuti setelah pengajuan ini (asumsi sederhana: potong N-1 dulu, baru N)
    $sisaDipotong = $pengajuanCuti->lama_cuti ?? 0;
    $potongN1 = min($sisaDipotong, $saldoN1);
    $sisaDipotong -= $potongN1;
    $potongN = min($sisaDipotong, $saldoN);
    $sisaN1 = $saldoN1 - $potongN1;
    $sisaN  = $saldoN - $potongN;

    // --- Keputusan atasan langsung & pejabat ---
    $kanitApproved = strtolower($pengajuanCuti->keputusan_kanit ?? '') === 'disetujui';
    $kanitRejected = !empty($pengajuanCuti->keputusan_kanit) && !in_array(strtolower($pengajuanCuti->keputusan_kanit), ['disetujui', 'dilewati']);

    $kasubagApproved = strtolower($pengajuanCuti->keputusan_kasubag ?? '') === 'disetujui';
    $kasubagRejected = !empty($pengajuanCuti->keputusan_kasubag) && !in_array(strtolower($pengajuanCuti->keputusan_kasubag), ['disetujui', 'dilewati']);

    $pejabatApproved = strtolower($pengajuanCuti->keputusan_pejabat ?? '') === 'disetujui';
    $pejabatRejected = !empty($pengajuanCuti->keputusan_pejabat) && !in_array(strtolower($pengajuanCuti->keputusan_pejabat), ['disetujui', 'dilewati']);

    // --- Operasional flow: Kepala Unit, Kepala Seksi, Kanit Kepegawaian, Kasubag TU ---
    $isOperasional = ($pengajuanCuti->tipe_aliran ?? 'administrasi') === 'operasional';

    $formatBox = function ($keputusan, $approver, $alasan, $canSign = true, $showRejectedReason = false) {
        $kep = strtolower($keputusan ?? '');
        if ($kep === '' || $kep === 'dilewati') {
            return '';
        }

        $rejected = ! in_array($kep, ['disetujui', 'dilewati'], true);

        $out = '';
        $sig = $canSign && $approver ? getSignatureBase64($approver->signature_path) : null;
        if ($sig) {
            $out .= "<img src=\"{$sig}\" class=\"signature-img\"><br>";
        } elseif ($approver) {
            $out .= '<br><br>';
        }
        if ($approver) {
            $out .= "<u>{$approver->nama}</u><br>";
            $out .= 'NIP. ' . $approver->nip . '<br>';
            if ($rejected) {
                $out .= '<span style="font-size: 9pt;">' . ($approver->pangkat_gol ?? '-') . '</span>';
            }
        }
        if ($rejected && $showRejectedReason && $alasan) {
            $out .= '<br><i>(' . $alasan . ')</i>';
        }
        return $out;
    };
@endphp

{{-- ===================== KOP SURAT ===================== --}}
<div class="surat-header">
    <table>
        <tr><td style="white-space: nowrap;">Palu, {{ $pengajuanCuti->created_at?->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Kepada</td></tr>
        <tr>
            <td class="label-yth">Yth.</td>
            <td>{{ $tujuanJabatan }}</td>
        </tr>
        <tr><td>di</td></tr>
        <tr><td class="kota-tujuan">{{ $tujuanKota }}</td></tr>
    </table>
</div>


<div class="judul-formulir">FORMULIR PERMINTAAN DAN PEMBERIAN CUTI</div>

<table class="form-table">
    <colgroup>
        <col style="width:14.4%"><col style="width:12.4%"><col style="width:13.9%">
        <col style="width:13.3%"><col style="width:16.1%"><col style="width:9.9%">
        <col style="width:20.0%">
    </colgroup>
    <tbody>
    {{-- ===================== I. DATA PEGAWAI ===================== --}}
    <tr><td colspan="7" class="section-title">I. DATA PEGAWAI</td></tr>
    <tr>
        <td>Nama</td>
        <td colspan="3">{{ $pengajuanCuti->user->nama }}</td>
        <td>NIP.</td>
        <td colspan="2">{{ $pengajuanCuti->user->nip }}</td>
    </tr>
    <tr>
        <td>Jabatan</td>
        <td colspan="3">{{ $pengajuanCuti->user->jabatan }}</td>
        <td>Pangkat /Gol.</td>
        <td colspan="2">{{ $pengajuanCuti->user->pangkat_golongan ?? '-' }}</td>
    </tr>
    <tr>
        <td>Unit Kerja</td>
        <td colspan="3">{{ $pengajuanCuti->user->unitKerja?->nama_unit }}</td>
        <td>Masa Kerja</td>
        <td colspan="2">{{ $masaKerja }}</td>
    </tr>
    <tr><td colspan="7" style="border:none; padding:4px 0;"></td></tr>
    {{-- ===================== II. JENIS CUTI YANG DIAMBIL ===================== --}}
    <tr><td colspan="7" class="section-title">II. JENIS CUTI YANG DIAMBIL</td></tr>
    <tr>
        <td colspan="2">1. Cuti Tahunan / Bersama</td>
        <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('tahunan') !!}</span></td>
        <td colspan="3">2. Cuti Besar</td>
        <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('besar') !!}</span></td>
    </tr>
    <tr>
        <td colspan="2">3. Cuti Sakit</td>
        <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('sakit') !!}</span></td>
        <td colspan="3">4. Cuti Melahirkan</td>
        <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('melahirkan') !!}</span></td>
    </tr>
    <tr>
        <td colspan="2">5. Cuti Karena Alasan Penting</td>
        <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('alasan_penting') !!}</span></td>
        <td colspan="3">6. Cuti di luar Tanggungan Negara</td>
        <td class="tc"><span style="font-family:&quot;DejaVu Sans&quot;, sans-serif;">{!! $tandaJenis('diluar_tanggungan_negara') !!}</span></td>
    </tr>
    <tr><td colspan="7" style="border:none; padding:4px 0;"></td></tr>
    {{-- ===================== III. ALASAN CUTI ===================== --}}
    <tr><td colspan="7" class="section-title">III. ALASAN CUTI</td></tr>
    <tr><td colspan="7">{{ $pengajuanCuti->alasan_cuti }}</td></tr>
    <tr><td colspan="7" style="border:none; padding:4px 0;"></td></tr>
    {{-- ===================== IV. LAMANYA CUTI ===================== --}}
    <tr><td colspan="7" class="section-title">IV. LAMANYA CUTI</td></tr>
    <tr>
        <td>Selama</td>
        <td colspan="2" class="tc">{{ $pengajuanCuti->lama_cuti }} Hari</td>
        <td class="tc">Mulai Tanggal</td>
        <td>{{ $pengajuanCuti->tanggal_mulai?->translatedFormat('d F Y') }}</td>
        <td class="tc">s/d</td>
        <td>{{ $pengajuanCuti->tanggal_selesai?->translatedFormat('d F Y') }}</td>
    </tr>
    <tr><td colspan="7" style="border:none; padding:4px 0;"></td></tr>
    {{-- ===================== V. CATATAN CUTI ===================== --}}
    <tr><td colspan="7" class="section-title">V. CATATAN CUTI</td></tr>
    <!-- <tr>
        <td colspan="3">1. CUTI TAHUNAN</td>
        <td colspan="3">2. CUTI BESAR</td>
        <td class="tc" colspan="2">test</td>
    </tr> -->
    <tr>
        <td colspan="3">1. CUTI TAHUNAN</td>
        <td colspan="3">2. CUTI BESAR</td>
        <td class="tc">{{ $fmtSaldo($pengajuanCuti->user->saldoCuti?->saldo_cuti_besar ?? 0) }}</td>
    </tr>
    <tr>
        <td colspan="2">Tahun :</td>
        <td class="tc">Keterangan</td>
        <td colspan="3">3. CUTI SAKIT</td>
        <td class="tc">{{ $fmtSaldo($pengajuanCuti->user->saldoCuti?->saldo_cuti_sakit ?? 0) }}</td>
    </tr>
    <tr>
        <td colspan="2">N-2 : {{ $fmtSaldo($saldoN2) }}</td>
        <td class="tc">{{ $tahunN2 }}</td>
        <td colspan="3">4. CUTI MELAHIRKAN</td>
        <td class="tc">{{ $fmtSaldo($pengajuanCuti->user->saldoCuti?->saldo_cuti_melahirkan ?? 0) }}</td>
    </tr>
    <tr>
        <td colspan="2">N-1 : {{ $fmtSaldo($saldoN1) }}</td>
        <td class="tc">{{ $tahunN1 }}</td>
        <td colspan="3">5. CUTI KARENA ALASAN PENTING</td>
        <td class="tc">{{ $fmtSaldo($pengajuanCuti->user->saldoCuti?->saldo_cuti_alasan_penting ?? 0) }}</td>
    </tr>
    <tr>
        <td colspan="2">N : {{ $fmtSaldo($saldoN) }}</td>
        <td class="tc">{{ $tahunN }}</td>
        <td colspan="3">6. CUTI DILUAR TANGGUNGAN NEGARA</td>
        <td class="tc">-</td>
    </tr>
    <tr>
        <td colspan="7">Sisa Cuti : Tahun {{ $tahunN1 }} {{ $sisaN1 }} Hari, {{ $tahunN }} {{ $sisaN }} Hari</td>
    </tr>
    <tr><td colspan="7" style="border:none; padding:4px 0;"></td></tr>
    {{-- ===================== VI. ALAMAT SELAMA MENJALANKAN CUTI ===================== --}}
    <tr><td colspan="7" class="section-title">VI. ALAMAT SELAMA MENJALANKAN CUTI</td></tr>
    <tr>
        <td colspan="4"></td>
        <td>TELP / HP</td>
        <td colspan="2">{{ $pengajuanCuti->user->nomor_telp ?? '-' }}</td>
    </tr>
    <tr>
        <td colspan="4" class="top tall-box-lg">{{ $pengajuanCuti->alamat_selama_cuti }}</td>
        <td colspan="3" class="top">
            Hormat Saya,<br><br>
            @if($sig = getSignatureBase64($pengajuanCuti->user->signature_path))
                <img src="{{ $sig }}" class="signature-img"><br>
            @else
                <br><br><br>
            @endif
            {{ $pengajuanCuti->user->nama }}<br>
            NIP. {{ $pengajuanCuti->user->nip }}<br>
        </td>
    </tr>
    <tr><td colspan="7" style="border:none; padding:4px 0;"></td></tr>
    {{-- ===================== VII. PERTIMBANGAN ATASAN LANGSUNG ===================== --}}
    </tbody><tbody style="page-break-inside: avoid;">
    <tr><td colspan="7" class="section-title">VII. PERTIMBANGAN ATASAN LANGSUNG</td></tr>
    <tr>
        <td colspan="7" style="padding: 0; border: none;">
            <table class="persetujuan-table">
                <tr>
                    <td colspan="3" class="persetujuan-title">DISETUJUI</td>
                    <td colspan="3" class="persetujuan-title">DITANGGUHKAN / TIDAK DISETUJUI</td>
                </tr>
                <tr>
                    <td class="persetujuan-title" style="font-size: 8pt;">KOORDINATOR/KASI</td>
                    <td class="persetujuan-title" style="font-size: 8pt;">KANIT KEPEGAWAIAN</td>
                    <td class="persetujuan-title" style="font-size: 8pt;">KASUBAG TU</td>
                    <td class="persetujuan-title" style="font-size: 8pt;">KOORDINATOR/KASI</td>
                    <td class="persetujuan-title" style="font-size: 8pt;">KANIT KEPEGAWAIAN</td>
                    <td class="persetujuan-title" style="font-size: 8pt;">KASUBAG TU</td>
                </tr>
                <tr>
                    @php
                        // Logic to map the approval to these 6 cells.
                        // We will map 'Kepala Seksi'/'Kanit' to KOORDINATOR/KASI
                        // 'Kanit Kepegawaian' to KANIT KEPEGAWAIAN
                        // 'Kasubag TU'/'Kasubag' to KASUBAG TU
                        
                        $kasiApprove = $isOperasional ? ($pengajuanCuti->keputusan_kepala_seksi === 'disetujui') : ($kanitApproved);
                        $kasiReject = $isOperasional ? (in_array($pengajuanCuti->keputusan_kepala_seksi, ['ditolak_kepala_seksi', 'ditolak', 'perubahan'])) : ($kanitRejected);
                        $kasiApprover = $isOperasional ? $pengajuanCuti->kepalaSeksi : $pengajuanCuti->kanit;
                        $kasiAlasan = $isOperasional ? $pengajuanCuti->alasan_kepala_seksi : $pengajuanCuti->alasan_kanit;
                        
                        $kanitPegApprove = $isOperasional ? ($pengajuanCuti->keputusan_kanit_kepegawaian === 'disetujui') : false;
                        $kanitPegReject = $isOperasional ? (in_array($pengajuanCuti->keputusan_kanit_kepegawaian, ['ditolak_kanit_kepegawaian', 'ditolak', 'perubahan'])) : false;
                        $kanitPegApprover = $isOperasional ? $pengajuanCuti->kanitKepegawaian : null;
                        $kanitPegAlasan = $isOperasional ? $pengajuanCuti->alasan_kanit_kepegawaian : null;
                        
                        $kasubagApprove = $isOperasional ? ($pengajuanCuti->keputusan_kasubag_tu === 'disetujui') : ($kasubagApproved);
                        $kasubagReject = $isOperasional ? (in_array($pengajuanCuti->keputusan_kasubag_tu, ['ditolak_kasubag_tu', 'ditolak', 'perubahan'])) : ($kasubagRejected);
                        $kasubagApprover = $isOperasional ? $pengajuanCuti->kasubagTu : $pengajuanCuti->kasubag;
                        $kasubagAlasan = $isOperasional ? $pengajuanCuti->alasan_kasubag_tu : $pengajuanCuti->alasan_kasubag;
                    @endphp
                    
                    <td class="approval-cell" style="vertical-align: middle;">
                        @if($kasiApprove)
                            {!! $formatBox('disetujui', $kasiApprover, null, true, false) !!}
                        @endif
                    </td>
                    <td class="approval-cell" style="vertical-align: middle;">
                        @if($kanitPegApprove)
                            {!! $formatBox('disetujui', $kanitPegApprover, null, true, false) !!}
                        @endif
                    </td>
                    <td class="approval-cell" style="vertical-align: middle;">
                        @if($kasubagApprove)
                            {!! $formatBox('disetujui', $kasubagApprover, null, true, false) !!}
                        @endif
                    </td>
                    
                    <td class="approval-cell" style="vertical-align: middle;">
                        @if($kasiReject)
                            {!! $formatBox('ditolak', $kasiApprover, $kasiAlasan, false, true) !!}
                        @endif
                    </td>
                    <td class="approval-cell" style="vertical-align: middle;">
                        @if($kanitPegReject)
                            {!! $formatBox('ditolak', $kanitPegApprover, $kanitPegAlasan, false, true) !!}
                        @endif
                    </td>
                    <td class="approval-cell" style="vertical-align: middle;">
                        @if($kasubagReject)
                            {!! $formatBox('ditolak', $kasubagApprover, $kasubagAlasan, false, true) !!}
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ===================== VIII. KEPUTUSAN PEJABAT YANG BERWENANG ===================== --}}
    @php
        $blangko = $pengajuanCuti->blangkoCuti;
        $kabandaraApproved = $blangko && strtolower($blangko->status) === 'disetujui';
        $kabandaraRejected = $blangko && in_array(strtolower($blangko->status), ['ditolak', 'perubahan', 'ditangguhkan']);
        $alasanKabandara = $blangko ? $blangko->alasan : null;
        
        $kabandaraNama = $blangko ? $blangko->kabandara_nama : null;
        $kabandaraNip = $blangko ? $blangko->kabandara_nip : null;
        $kabandaraPangkat = $blangko ? $blangko->kabandara_pangkat : null;
        
        // Fallback to relation if snapshot is empty but relation exists
        if (!$kabandaraNama && $blangko && $blangko->kabandara) {
            $kabandaraNama = $blangko->kabandara->nama;
            $kabandaraNip = $blangko->kabandara->nip;
            $kabandaraPangkat = $blangko->kabandara->pangkat_gol;
        }
        
        $signaturePath = ($blangko && $blangko->kabandara) ? $blangko->kabandara->signature_path : null;
    @endphp
    <tr><td colspan="7" style="border:none; padding:4px 0;"></td></tr>
    </tbody><tbody style="page-break-before: always; page-break-inside: avoid;">
    <tr><td colspan="7" class="section-title">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI</td></tr>
    <tr>
        <td colspan="4" class="tc">DISETUJUI</td>
        <td colspan="3" class="tc">DITANGGUHKAN / TIDAK DISETUJUI</td>
    </tr>
    <tr>
        <td colspan="4" class="tc tall-box">
            @if($kabandaraApproved)
                @if($signaturePath && $sig = getSignatureBase64($signaturePath))
                    <img src="{{ $sig }}" class="signature-img"><br>
                @else
                    <br><br><br>
                @endif
                <u>{{ $kabandaraNama ?? '...................' }}</u><br>
                NIP. {{ $kabandaraNip ?? '...................' }}<br>
            @endif
        </td>
        <td colspan="3" class="tc tall-box">
            @if($kabandaraRejected)
                @if($signaturePath && $sig = getSignatureBase64($signaturePath))
                    <img src="{{ $sig }}" class="signature-img"><br>
                @else
                    <br><br><br>
                @endif
                <u>{{ $kabandaraNama ?? '...................' }}</u><br>
                NIP. {{ $kabandaraNip ?? '...................' }}<br>
                Pangkat/Gol. {{ $kabandaraPangkat ?? '-' }}<br>
                <i>({{ $alasanKabandara }})</i>
            @endif
        </td>
    </tr>

    </tbody>
</table>

</body>
</html>
