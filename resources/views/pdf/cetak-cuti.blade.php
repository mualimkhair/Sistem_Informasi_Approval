<!DOCTYPE html>
<html>
<head>
    <title>Formulir Cuti</title>
    <style>
        {{--
            CATATAN UNTUK DEVELOPER (asumsi yang dipakai saat konversi dari Excel):
            1. $pengajuanCuti->user->pangkat_golongan  -> kolom "Pangkat/Gol." belum ada di blade lama,
               sesuaikan nama kolom di tabel users jika berbeda.
            2. $pengajuanCuti->user->no_hp             -> kolom "TELP / HP" belum ada di blade lama,
               sesuaikan nama kolom jika berbeda (mis. no_telepon, phone, dst).
            4. Perhitungan "Sisa Cuti" di bagian V memakai asumsi sederhana: saldo N-1 dipotong dulu,
               sisanya baru memotong saldo N. Sesuaikan dengan service/kalkulasi saldo cuti yang
               sudah ada di sistem Anda jika logikanya berbeda.
            5. Kotak tanda tangan Kanit/Kasubag (bag. VII) & Pejabat (bag. VIII) ditampilkan berdasarkan
               keputusan_kanit / keputusan_kasubag / keputusan_pejabat: nilai 'disetujui' dianggap
               approved, nilai lain (tidak null) dianggap ditangguhkan/tidak disetujui, null = kosong
               (belum ada keputusan).
        --}}
        @page {
            margin: 1in 0.45in 1in 0.7in;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
        }
        .surat-header {
            width: 46%;
            margin-left: 54%;
            margin-bottom: 12px;
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
            margin: 6px 0 14px 0;
        }

        table.form-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .form-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
            text-align: left;
        }
        .form-table td.tc { text-align: center; }
        .form-table td.top { vertical-align: top; }

        .section-title { font-weight: normal; }
        .tall-box { height: 60px; }
        .tall-box-lg { height: 100px; }

        .signature-img { max-width: 90px; max-height: 70px; }
    </style>
</head>
<body>

@php
    \Carbon\Carbon::setLocale('id');

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
    $tandaJenis = fn ($tipe) => $pengajuanCuti->jenis_cuti === $tipe ? '√' : '-';

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
    $kanitRejected = !empty($pengajuanCuti->keputusan_kanit) && !$kanitApproved;

    $kasubagApproved = strtolower($pengajuanCuti->keputusan_kasubag ?? '') === 'disetujui';
    $kasubagRejected = !empty($pengajuanCuti->keputusan_kasubag) && !$kasubagApproved;

    $pejabatApproved = strtolower($pengajuanCuti->keputusan_pejabat ?? '') === 'disetujui';
    $pejabatRejected = !empty($pengajuanCuti->keputusan_pejabat) && !$pejabatApproved;
@endphp

{{-- ===================== KOP SURAT ===================== --}}
<div class="surat-header">
    <table>
        <tr><td>Palu, {{ $pengajuanCuti->created_at?->translatedFormat('d F Y') }}</td></tr>
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
</table>
<br>
<table class="form-table">
    <colgroup>
        <col style="width:14.4%"><col style="width:12.4%"><col style="width:13.9%">
        <col style="width:13.3%"><col style="width:16.1%"><col style="width:9.9%">
        <col style="width:20.0%">
    </colgroup>
    <tbody>
    {{-- ===================== II. JENIS CUTI YANG DIAMBIL ===================== --}}
    <tr><td colspan="7" class="section-title">II. JENIS CUTI YANG DIAMBIL</td></tr>
    <tr>
        <td colspan="2">1. Cuti Tahunan / Bersama</td>
        <td class="tc">{{ $tandaJenis('tahunan') }}</td>
        <td colspan="3">2. Cuti Besar</td>
        <td class="tc">{{ $tandaJenis('besar') }}</td>
    </tr>
    <tr>
        <td colspan="2">3. Cuti Sakit</td>
        <td class="tc">{{ $tandaJenis('sakit') }}</td>
        <td colspan="3">4. Cuti Melahirkan</td>
        <td class="tc">{{ $tandaJenis('melahirkan') }}</td>
    </tr>
    <tr>
        <td colspan="2">5. Cuti Karena Alasan Penting</td>
        <td class="tc">{{ $tandaJenis('alasan_penting') }}</td>
        <td colspan="3">6. Cuti di luar Tanggungan Negara</td>
        <td class="tc">{{ $tandaJenis('diluar_tanggungan_negara') }}</td>
    </tr>
</table>
<br>
<table class="form-table">
    <colgroup>
        <col style="width:14.4%"><col style="width:12.4%"><col style="width:13.9%">
        <col style="width:13.3%"><col style="width:16.1%"><col style="width:9.9%">
        <col style="width:20.0%">
    </colgroup>
    <tbody>
    {{-- ===================== III. ALASAN CUTI ===================== --}}
    <tr><td colspan="7" class="section-title">III. ALASAN CUTI</td></tr>
    <tr><td colspan="7">{{ $pengajuanCuti->alasan_cuti }}</td></tr>
</table>
<br>
<table class="form-table">
    <colgroup>
        <col style="width:14.4%"><col style="width:12.4%"><col style="width:13.9%">
        <col style="width:13.3%"><col style="width:16.1%"><col style="width:9.9%">
        <col style="width:20.0%">
    </colgroup>
    <tbody>
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
</table>
<br>
<table class="form-table">
    <colgroup>
        <col style="width:14.4%"><col style="width:12.4%"><col style="width:13.9%">
        <col style="width:13.3%"><col style="width:16.1%"><col style="width:9.9%">
        <col style="width:20.0%">
    </colgroup>
    <tbody>
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
</table>
<br>
<table class="form-table">
    <colgroup>
        <col style="width:14.4%"><col style="width:12.4%"><col style="width:13.9%">
        <col style="width:13.3%"><col style="width:16.1%"><col style="width:9.9%">
        <col style="width:20.0%">
    </colgroup>
    <tbody>
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
            @if($pengajuanCuti->user->signature_path)
                <img src="{{ public_path('storage/' . $pengajuanCuti->user->signature_path) }}" class="signature-img"><br>
            @else
                <br><br><br>
            @endif
            {{ $pengajuanCuti->user->nama }}<br>
            NIP. {{ $pengajuanCuti->user->nip }}
        </td>
    </tr>
</table>
<br>
<table class="form-table">
    <colgroup>
        <col style="width:14.4%"><col style="width:12.4%"><col style="width:13.9%">
        <col style="width:13.3%"><col style="width:16.1%"><col style="width:9.9%">
        <col style="width:20.0%">
    </colgroup>
    <tbody>
    {{-- ===================== VII. PERTIMBANGAN ATASAN LANGSUNG ===================== --}}
    <tr><td colspan="7" class="section-title">VII. PERTIMBANGAN ATASAN LANGSUNG</td></tr>
    <tr>
        <td colspan="4" class="tc">DISETUJUI</td>
        <td colspan="3" class="tc">DITANGGUHKAN / TIDAK DISETUJUI</td>
    </tr>
    <tr>
        <td colspan="2" class="tc">KOORDINATOR / KANIT</td>
        <td colspan="2" class="tc">KASI / KASUBAG</td>
        <td colspan="2" class="tc">KOORDINATOR / KANIT</td>
        <td class="tc">KASI / KASUBAG</td>
    </tr>
    <tr>
        <td colspan="2" class="tc tall-box">
            @if($kanitApproved)
                @if($pengajuanCuti->kanit)
                    @if($pengajuanCuti->kanit->signature_path)
                        <img src="{{ public_path('storage/' . $pengajuanCuti->kanit->signature_path) }}" class="signature-img"><br>
                    @else
                        <br><br><br>
                    @endif
                    <u>{{ $pengajuanCuti->kanit->nama }}</u><br>
                    NIP. {{ $pengajuanCuti->kanit->nip }}
                @else
                    √<br>{{ $pengajuanCuti->alasan_kanit }}
                @endif
            @endif
        </td>
        <td colspan="2" class="tc tall-box">
            @if($kasubagApproved)
                @if($pengajuanCuti->kasubag)
                    @if($pengajuanCuti->kasubag->signature_path)
                        <img src="{{ public_path('storage/' . $pengajuanCuti->kasubag->signature_path) }}" class="signature-img"><br>
                    @else
                        <br><br><br>
                    @endif
                    <u>{{ $pengajuanCuti->kasubag->nama }}</u><br>
                    NIP. {{ $pengajuanCuti->kasubag->nip }}
                @else
                    √<br>{{ $pengajuanCuti->alasan_kasubag }}
                @endif
            @endif
        </td>
        <td colspan="2" class="tc tall-box">
            @if($kanitRejected)
                @if($pengajuanCuti->kanit)
                    @if($pengajuanCuti->kanit->signature_path)
                        <img src="{{ public_path('storage/' . $pengajuanCuti->kanit->signature_path) }}" class="signature-img"><br>
                    @else
                        <br><br><br>
                    @endif
                    <u>{{ $pengajuanCuti->kanit->nama }}</u><br>
                    NIP. {{ $pengajuanCuti->kanit->nip }}<br>
                    <i>({{ $pengajuanCuti->alasan_kanit }})</i>
                @else
                    √<br>{{ $pengajuanCuti->alasan_kanit }}
                @endif
            @endif
        </td>
        <td class="tc tall-box">
            @if($kasubagRejected)
                @if($pengajuanCuti->kasubag)
                    @if($pengajuanCuti->kasubag->signature_path)
                        <img src="{{ public_path('storage/' . $pengajuanCuti->kasubag->signature_path) }}" class="signature-img"><br>
                    @else
                        <br><br><br>
                    @endif
                    <u>{{ $pengajuanCuti->kasubag->nama }}</u><br>
                    NIP. {{ $pengajuanCuti->kasubag->nip }}<br>
                    <i>({{ $pengajuanCuti->alasan_kasubag }})</i>
                @else
                    √<br>{{ $pengajuanCuti->alasan_kasubag }}
                @endif
            @endif
        </td>
    </tr>
</table>
<br>
<table class="form-table">
    <colgroup>
        <col style="width:14.4%"><col style="width:12.4%"><col style="width:13.9%">
        <col style="width:13.3%"><col style="width:16.1%"><col style="width:9.9%">
        <col style="width:20.0%">
    </colgroup>
    <tbody>
    {{-- ===================== VIII. KEPUTUSAN PEJABAT YANG BERWENANG ===================== --}}
    <tr><td colspan="7" class="section-title">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI</td></tr>
    <tr>
        <td colspan="4" class="tc">DISETUJUI</td>
        <td colspan="3" class="tc">DITANGGUHKAN / TIDAK DISETUJUI</td>
    </tr>
    <tr>
        <td colspan="4" class="tc tall-box">
            @if($pejabatApproved)
                @if($pengajuanCuti->pejabat)
                    @if($pengajuanCuti->pejabat->signature_path)
                        <img src="{{ public_path('storage/' . $pengajuanCuti->pejabat->signature_path) }}" class="signature-img"><br>
                    @else
                        <br><br><br>
                    @endif
                    <u>{{ $pengajuanCuti->pejabat->nama }}</u><br>
                    NIP. {{ $pengajuanCuti->pejabat->nip }}
                @else
                    √<br>{{ $pengajuanCuti->alasan_pejabat }}
                @endif
            @endif
        </td>
        <td colspan="3" class="tc tall-box">
            @if($pejabatRejected)
                @if($pengajuanCuti->pejabat)
                    @if($pengajuanCuti->pejabat->signature_path)
                        <img src="{{ public_path('storage/' . $pengajuanCuti->pejabat->signature_path) }}" class="signature-img"><br>
                    @else
                        <br><br><br>
                    @endif
                    <u>{{ $pengajuanCuti->pejabat->nama }}</u><br>
                    NIP. {{ $pengajuanCuti->pejabat->nip }}<br>
                    <i>({{ $pengajuanCuti->alasan_pejabat }})</i>
                @else
                    √<br>{{ $pengajuanCuti->alasan_pejabat }}
                @endif
            @endif
        </td>
    </tr>

    </tbody>
</table>

</body>
</html>
