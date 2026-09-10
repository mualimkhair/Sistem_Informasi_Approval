<!DOCTYPE html>
<html>
<head>
    <title>SURAT IZIN CUTI SAKIT</title>
    <style>
        @page { margin: 38mm 15mm 15mm 15mm; size: A4 portrait; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.5; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .text-underline { text-decoration: underline; }
        .title { font-size: 14pt; margin-bottom: 0; }
        .subtitle { font-size: 12pt; margin-top: 0; margin-bottom: 10px; }
        .content { margin-top: 10px; }
        .table-identitas { width: 100%; border-collapse: collapse; margin-left: 20px; margin-bottom: 10px; }
        .table-identitas td { padding: 2px 5px; vertical-align: top; }
        .table-identitas .col-label { width: 35%; }
        .table-identitas .col-colon { width: 5%; }
        .table-identitas .col-value { width: 60%; }
        
        .table-proses { width: 100%; border-collapse: collapse; margin-top:  15px; font-size: 11pt;  page-break-inside: avoid; }
        .table-proses th, .table-proses td { border: 1px solid black; padding: 5px; text-align: left; vertical-align: middle; }
        
        .signature-area { width: 50%; float: right; margin-top: 15px; }
        .signature-img { max-height: 90px; }
        .clear { clear: both; }
        .list-ketentuan { padding-left: 40px; margin-top: 5px; }
    </style>
</head>
<body>
    @php
        \Carbon\Carbon::setLocale('id');
        $blangko = $pengajuanCuti->blangkoCuti;
        $nomor_surat = $blangko?->nomor_surat ?? '................';
        $tahun = $pengajuanCuti->created_at ? $pengajuanCuti->created_at->format('Y') : date('Y');
        
        $nama = $pengajuanCuti->user->nama ?? '................';
        $nip = $pengajuanCuti->user->nip ?? '................';
        $pangkat = $pengajuanCuti->user->pangkat_gol ?? '................';
        $jabatan = $pengajuanCuti->user->jabatan ?? '................';
        $satuan_organisasi = $pengajuanCuti->user->unitKerja?->nama_unit ?? 'Kantor BLU UPBU Mutiara Sis Al-Jufri';
        
        $jumlah_hari = $pengajuanCuti->lama_cuti ?? '...';
        $tanggal_mulai = $pengajuanCuti->tanggal_mulai ? $pengajuanCuti->tanggal_mulai->translatedFormat('d F Y') : '................';
        $tanggal_selesai = $pengajuanCuti->tanggal_selesai ? $pengajuanCuti->tanggal_selesai->translatedFormat('d F Y') : '................';
        
        $tanggal_surat = $blangko?->tanggal_keputusan ? $blangko->tanggal_keputusan->translatedFormat('d F Y') : '................';
        
        $kabandara_nama = $blangko?->kabandara_nama ?? $blangko?->kabandara?->nama ?? '..........................';
        $kabandara_nip = $blangko?->kabandara_nip ?? $blangko?->kabandara?->nip ?? '..........................';
        $kabandara_pangkat = $blangko?->kabandara_pangkat ?? $blangko?->kabandara?->pangkat_gol ?? '';
        $kabandara_jabatan = $blangko?->kabandara?->jabatan ?? 'KEPALA KANTOR,';
        
        $kabandara_signature_path = $blangko?->kabandara?->signature_path;

        $kanit = $pengajuanCuti->kanitKepegawaian;
        $kasubag = $pengajuanCuti->kasubagTu;
        $kanit_nama = $kanit ? $kanit->nama : '.......................';
        $kasubag_nama = $kasubag ? $kasubag->nama : '.......................';
        $kanit_tanggal = $pengajuanCuti->keputusan_kanit_kepegawaian ? $pengajuanCuti->updated_at->translatedFormat('d F Y') : '.............';
        $kasubag_tanggal = $pengajuanCuti->keputusan_kasubag_tu ? $pengajuanCuti->updated_at->translatedFormat('d F Y') : '.............';
        $kanit_paraf = $kanit ? getSignatureBase64($kanit->signature_path) : null;
        $kasubag_paraf = $kasubag ? getSignatureBase64($kasubag->signature_path) : null;
    
        
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
    @endphp

    <div class="text-center">
        <div class="title text-bold text-underline">SURAT IZIN CUTI SAKIT</div>
        <div class="text-bold">Nomor : SI. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tahun {{ $tahun }}</div>
    </div>

    <div class="content">
        <div>1. Diberikan cuti sakit kepada Pegawai Negeri Sipil :</div>
        <table class="table-identitas">
            <tr>
                <td class="col-label">N a m a</td>
                <td class="col-colon">:</td>
                <td class="col-value">{{ $nama }}</td>
            </tr>
            <tr>
                <td class="col-label">N I P</td>
                <td class="col-colon">:</td>
                <td class="col-value">{{ $nip }}</td>
            </tr>
            <tr>
                <td class="col-label">Pangkat/ Golongan Ruang</td>
                <td class="col-colon">:</td>
                <td class="col-value">{{ $pangkat }}</td>
            </tr>
            <tr>
                <td class="col-label">J a b a t a n</td>
                <td class="col-colon">:</td>
                <td class="col-value">{{ $jabatan }}</td>
            </tr>
            <tr>
                <td class="col-label">Satuan Organisasi</td>
                <td class="col-colon">:</td>
                <td class="col-value">{{ $satuan_organisasi }}</td>
            </tr>
        </table>

        <div style="text-align: justify;">
            Selama {{ $jumlah_hari }} hari, terhitung mulai tanggal {{ $tanggal_mulai }} sampai dengan {{ $tanggal_selesai }}, dengan ketentuan setelah berakhir jangka waktu cuti sakit tersebut, wajib melaporkan diri kepada atasan langsungnya dan bekerja sebagaimana mestinya.
        </div>

        <div style="text-align: justify; margin-top: 10px;">
            2. Demikian Surat Izin Cuti Sakit ini dibuat untuk dipergunakan sebagaimana mestinya.
        </div>
    </div>

    <div class="signature-area">
        <div>Palu, &nbsp;&nbsp;&nbsp;{{ $tanggal_surat }}</div>
        <br>
        <div class="text-bold">{{ strtoupper($kabandara_jabatan) }}</div>
        <br><br>
        @if($kabandara_signature_path && $sig = getSignatureBase64($kabandara_signature_path))
            <img src="{{ $sig }}" class="signature-img"><br>
        @else
            <br><br>
        @endif
        <div class="text-bold text-underline">{{ $kabandara_nama }}</div>
        @if($kabandara_pangkat)
        <div class="text-bold">{{ $kabandara_pangkat }}</div>
        @endif
        <div class="text-bold">NIP. {{ $kabandara_nip }}</div>
    </div>
    <div class="clear"></div>

    <table class="table-proses">
        <thead>
            <tr>
                <th>NO</th>
                <th>DIPROSES</th>
                <th>NAMA</th>
                <th>JABATAN</th>
                <th>TANGGAL</th>
                <th>PARAF</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1.</td>
                <td>Dibuat</td>
                <td></td>
                <td>Kepegawaian</td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>2.</td>
                <td>Diperiksa</td>
                <td>{{ $kanit_nama }}</td>
                <td>K. Kepegawaian</td>
                <td>{{ $kanit_tanggal }}</td>
                <td style="text-align: center; vertical-align: middle; padding: 2px;">
                    @if($kanit_paraf)
                        <img src="{{ $kanit_paraf }}" style="max-height: 35px;">
                    @endif
                </td>
            </tr>
            <tr>
                <td>3.</td>
                <td>Disetujui</td>
                <td>{{ $kasubag_nama }}</td>
                <td>KSTU</td>
                <td>{{ $kasubag_tanggal }}</td>
                <td style="text-align: center; vertical-align: middle; padding: 2px;">
                    @if($kasubag_paraf)
                        <img src="{{ $kasubag_paraf }}" style="max-height: 35px;">
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
