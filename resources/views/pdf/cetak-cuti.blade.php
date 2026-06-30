<!DOCTYPE html>
<html>
<head>
    <title>Formulir Cuti</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        td, th { border: 1px solid #000; padding: 5px; }
        .no-border td { border: none; padding: 2px 5px; }
        .signature-img { max-width: 100px; max-height: 80px; }
    </style>
</head>
<body>
    <div class="header">FORMULIR PERMINTAAN DAN PEMBERIAN CUTI</div>
    
    <table class="no-border">
        <tr>
            <td width="20%">Nama</td><td width="30%">: {{ $pengajuanCuti->user->nama }}</td>
            <td width="20%">NIP</td><td width="30%">: {{ $pengajuanCuti->user->nip }}</td>
        </tr>
        <tr>
            <td>Jabatan</td><td>: {{ $pengajuanCuti->user->jabatan }}</td>
            <td>Masa Kerja</td><td>: {{ $pengajuanCuti->user->tanggal_masuk?->diffInYears(now()) ?? 0 }} Tahun</td>
        </tr>
        <tr>
            <td>Unit Kerja</td><td colspan="3">: {{ $pengajuanCuti->user->unitKerja?->nama_unit }}</td>
        </tr>
    </table>

    <table>
        <tr><th colspan="4" style="text-align: left;">I. JENIS CUTI YANG DIAMBIL</th></tr>
        <tr>
            <td width="40%">1. Cuti Tahunan</td><td width="10%">{{ $pengajuanCuti->jenis_cuti == 'tahunan' ? 'V' : '' }}</td>
            <td width="40%">4. Cuti Melahirkan</td><td width="10%">{{ $pengajuanCuti->jenis_cuti == 'melahirkan' ? 'V' : '' }}</td>
        </tr>
        <tr>
            <td>2. Cuti Besar</td><td>{{ $pengajuanCuti->jenis_cuti == 'besar' ? 'V' : '' }}</td>
            <td>5. Cuti Karena Alasan Penting</td><td>{{ $pengajuanCuti->jenis_cuti == 'alasan_penting' ? 'V' : '' }}</td>
        </tr>
        <tr>
            <td>3. Cuti Sakit</td><td>{{ $pengajuanCuti->jenis_cuti == 'sakit' ? 'V' : '' }}</td>
            <td>6. Cuti di Luar Tanggungan Negara</td><td>{{ $pengajuanCuti->jenis_cuti == 'diluar_tanggungan_negara' ? 'V' : '' }}</td>
        </tr>
    </table>

    <table>
        <tr><th colspan="2" style="text-align: left;">II. ALASAN CUTI</th></tr>
        <tr><td colspan="2">{{ $pengajuanCuti->alasan_cuti }}</td></tr>
    </table>

    <table>
        <tr><th colspan="6" style="text-align: left;">III. LAMANYA CUTI</th></tr>
        <tr>
            <td width="10%">Selama</td><td width="15%">{{ $pengajuanCuti->lama_cuti }} (Hari)</td>
            <td width="15%">Mulai Tanggal</td><td width="20%">{{ $pengajuanCuti->tanggal_mulai?->format('d-m-Y') }}</td>
            <td width="10%">s/d</td><td width="30%">{{ $pengajuanCuti->tanggal_selesai?->format('d-m-Y') }}</td>
        </tr>
    </table>
    
    <table>
        <tr><th colspan="2" style="text-align: left;">IV. CATATAN CUTI</th></tr>
        <tr>
            <td width="50%">
                <b>Sisa Saldo Tahunan:</b><br>
                N: {{ $pengajuanCuti->user->saldoCuti?->saldo_n ?? 0 }} | 
                N-1: {{ $pengajuanCuti->user->saldoCuti?->saldo_n1 ?? 0 }} | 
                N-2: {{ $pengajuanCuti->user->saldoCuti?->saldo_n2 ?? 0 }}
            </td>
            <td width="50%">
                <b>Alamat Selama Cuti:</b><br>
                {{ $pengajuanCuti->alamat_selama_cuti }}
            </td>
        </tr>
    </table>
    
    <table>
        <tr><th colspan="3" style="text-align: left;">V. PERTIMBANGAN ATASAN LANGSUNG</th></tr>
        <tr>
            <td width="33%"><b>Keputusan Kanit:</b> {{ str_replace('_', ' ', strtoupper($pengajuanCuti->keputusan_kanit)) }}<br><i>{{ $pengajuanCuti->alasan_kanit }}</i></td>
            <td width="33%"><b>Keputusan Kasubag:</b> {{ str_replace('_', ' ', strtoupper($pengajuanCuti->keputusan_kasubag)) }}<br><i>{{ $pengajuanCuti->alasan_kasubag }}</i></td>
            <td width="33%"><b>Keputusan Pejabat:</b> {{ str_replace('_', ' ', strtoupper($pengajuanCuti->keputusan_pejabat)) }}<br><i>{{ $pengajuanCuti->alasan_pejabat }}</i></td>
        </tr>
    </table>

    <table class="no-border" style="margin-top: 20px;">
        <tr>
            <td width="60%"></td>
            <td style="text-align: center;">
                Palu, {{ $pengajuanCuti->created_at->format('d F Y') }}<br>
                Hormat Saya,<br>
                @if($pengajuanCuti->user->signature_path)
                    <img src="{{ public_path('storage/' . $pengajuanCuti->user->signature_path) }}" class="signature-img"><br>
                @else
                    <br><br><br>
                @endif
                <b><u>{{ $pengajuanCuti->user->nama }}</u></b><br>
                NIP. {{ $pengajuanCuti->user->nip }}
            </td>
        </tr>
    </table>
</body>
</html>
