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
    content = re.sub(r'@page\s*\{[^}]*\}', r'@page { margin: 38mm 15mm 15mm 15mm; size: A4 portrait; }', content)
    
    # Update signature image size
    content = re.sub(r'\.signature-img\s*\{[^\}]*\}', r'.signature-img { max-height: 90px; }', content)

    # Reduce margins and spacing to save space
    content = re.sub(r'\.subtitle\s*\{[^\}]*margin-bottom:\s*\d+px;[^\}]*\}', r'.subtitle { font-size: 12pt; margin-top: 0; margin-bottom: 10px; }', content)
    content = re.sub(r'\.content\s*\{[^\}]*margin-top:\s*\d+px;[^\}]*\}', r'.content { margin-top: 10px; }', content)
    content = re.sub(r'\.signature-area\s*\{[^\}]*margin-top:\s*\d+px;[^\}]*\}', r'.signature-area { width: 50%; float: right; margin-top: 15px; }', content)
    content = re.sub(r'\.table-proses\s*\{([^\}]*margin-top:\s*)\d+px;([^\}]*)\}', r'.table-proses {\1 15px;\2 page-break-inside: avoid; }', content)

    # Update PHP variables for paraf
    if "$kanit_paraf =" not in content:
        content = re.sub(
            r'(\$kasubag_tanggal = [^;]+;)', 
            r'\1\n        $kanit_paraf = $kanit ? getSignatureBase64($kanit->signature_path) : null;\n        $kasubag_paraf = $kasubag ? getSignatureBase64($kasubag->signature_path) : null;', 
            content
        )

    # Replace table body with paraf logic
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
        </tbody>"""
    
    content = re.sub(r'<tbody>.*?</tbody>', tbody, content, flags=re.DOTALL)
    
    with open(path, 'w') as f:
        f.write(content)

print("Updated all templates")
