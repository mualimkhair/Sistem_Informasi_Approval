import re

path = "resources/views/pdf/cetak-blangko.blade.php"
with open(path, 'r') as f:
    content = f.read()

# 1. Date nowrap
content = content.replace("<tr><td>Palu,", "<tr><td style=\"white-space: nowrap;\">Palu,")

# 2. Optimize padding in form-table
content = content.replace("padding: 5px 8px;", "padding: 3px 5px;")

# 3. Optimize approval cell height and padding
content = content.replace("height: 82px; padding: 30px 6px 6px;", "height: 60px; padding: 15px 4px 4px;")

# 4. Wrap Section VII in a separate tbody to avoid page break
# Section VII starts at: <tr><td colspan="7" class="section-title">VII. PERTIMBANGAN ATASAN LANGSUNG</td></tr>
# Before it, we close the previous tbody and open a new one.
section_vii_start = '<tr><td colspan="7" class="section-title">VII. PERTIMBANGAN ATASAN LANGSUNG</td></tr>'
content = content.replace(section_vii_start, f'</tbody><tbody style="page-break-inside: avoid;">\n    {section_vii_start}')

# 5. Wrap Section VIII in a separate tbody to avoid page break
section_viii_start = '<tr><td colspan="7" class="section-title">VIII. KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI</td></tr>'
content = content.replace(section_viii_start, f'</tbody><tbody style="page-break-inside: avoid;">\n    {section_viii_start}')

with open(path, 'w') as f:
    f.write(content)
