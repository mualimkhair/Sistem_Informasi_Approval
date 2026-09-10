import os
import re

directory = "resources/views/pdf/Kategori Surat Izin Cuti"

files = [
    "alasan-penting.blade.php",
    "bersalin.blade.php",
    "besar.blade.php",
    "tahunan.blade.php",
    "sakit.blade.php",
    "diluar-tanggungan-negara.blade.php"
]

for filename in files:
    path = os.path.join(directory, filename)
    with open(path, 'r') as f:
        content = f.read()

    # Update @page
    content = re.sub(r'@page\s*\{[^}]*\}', r'@page { margin: 40mm 20mm 20mm 20mm; size: A4 portrait; }', content)
    
    # Update signature image size
    content = re.sub(r'\.signature-img\s*\{[^\}]*\}', r'.signature-img { max-height: 120px; }', content)

    # Insert PHP variables for names and dates
    php_vars = """
        $kanit = $pengajuanCuti->kanitKepegawaian;
        $kasubag = $pengajuanCuti->kasubagTu;
        $kanit_nama = $kanit ? $kanit->nama : '.......................';
        $kasubag_nama = $kasubag ? $kasubag->nama : '.......................';
        $kanit_tanggal = $pengajuanCuti->keputusan_kanit_kepegawaian ? $pengajuanCuti->updated_at->translatedFormat('d F Y') : '.............';
        $kasubag_tanggal = $pengajuanCuti->keputusan_kasubag_tu ? $pengajuanCuti->updated_at->translatedFormat('d F Y') : '.............';
    """
    
    if "$kanit_nama" not in content:
        content = re.sub(r'(\$kabandara_signature_path = .*?;)', r'\1\n' + php_vars, content)

    # Replace table body
    tbody = """<tbody>
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
                <td></td>
            </tr>
            <tr>
                <td>3.</td>
                <td>Disetujui</td>
                <td>{{ $kasubag_nama }}</td>
                <td>KSTU</td>
                <td>{{ $kasubag_tanggal }}</td>
                <td></td>
            </tr>
        </tbody>"""
    
    content = re.sub(r'<tbody>.*?</tbody>', tbody, content, flags=re.DOTALL)
    
    with open(path, 'w') as f:
        f.write(content)

print("Updated all templates")
