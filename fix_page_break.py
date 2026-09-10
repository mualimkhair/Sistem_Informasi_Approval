import re

path = "resources/views/pdf/cetak-blangko.blade.php"
with open(path, 'r') as f:
    content = f.read()

# Replace the tbody for Section VIII to force a page break before it
content = content.replace(
    '</tbody><tbody style="page-break-inside: avoid;">\n    <tr><td colspan="7" class="section-title">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI</td></tr>',
    '</tbody><tbody style="page-break-before: always; page-break-inside: avoid;">\n    <tr><td colspan="7" class="section-title">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI</td></tr>'
)

with open(path, 'w') as f:
    f.write(content)

