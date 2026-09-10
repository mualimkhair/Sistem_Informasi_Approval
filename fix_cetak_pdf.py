import re

files = [
    "app/Filament/Resources/PersetujuanCutis/Tables/PersetujuanCutisTable.php",
    "app/Filament/Resources/PengajuanCutis/Tables/PengajuanCutisTable.php"
]

replacement = r"""Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn ($record) => ($record->blangkoCuti && $record->blangkoCuti->status === 'disetujui' && ($record->blangkoCuti->file_blangko_path || $record->blangkoCuti->file_surat_izin_path)) || auth()->user()->hasRole(['super_admin', 'admin']))
                    ->modalHeading('DOKUMEN CUTI')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn ($action) => $action->label('Tutup'))
                    ->modalContent(function ($record) {
                        $blangko = $record->blangkoCuti;
                        
                        $suratIzinName = match($record->jenis_cuti) {
                            'cuti_tahunan' => 'Surat Izin Cuti Tahunan',
                            'cuti_besar' => 'Surat Izin Cuti Besar',
                            'cuti_sakit' => 'Surat Izin Cuti Sakit',
                            'cuti_melahirkan' => 'Surat Izin Cuti Bersalin',
                            'cuti_alasan_penting' => 'Surat Izin Cuti Alasan Penting',
                            'cuti_diluar_tanggungan_negara' => 'Surat Izin Cuti di Luar Tanggungan Negara',
                            default => 'Surat Izin Cuti'
                        };

                        $html = '<div class="space-y-4">';
                        
                        if ($blangko && $blangko->file_blangko_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_blangko_path)) {
                            $urlBlangko = route('cetak-blangko', $record);
                            $html .= '<div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="font-bold text-lg mb-1">Blangko Cuti Final</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Formulir Permintaan dan Pemberian Cuti</p>
                                <div class="flex gap-2">
                                    <a href="'.$urlBlangko.'" target="_blank" style="background-color: rgb(217 119 6); padding: 0.5rem 1rem; border-radius: 0.5rem; color: white; font-weight: bold; text-decoration: none; display: inline-block;">
                                        Download / Preview Blangko Cuti
                                    </a>
                                </div>
                            </div>';
                        } else {
                            $html .= '<div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="font-bold text-lg mb-1">Blangko Cuti Final</h4>
                                <p class="text-sm text-red-500">Dokumen Blangko Cuti belum tersedia.</p>
                            </div>';
                        }

                        if ($blangko && $blangko->file_surat_izin_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_surat_izin_path)) {
                            $urlSurat = route('cetak-surat-izin-cuti', $record);
                            $html .= '<div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="font-bold text-lg mb-1">'.$suratIzinName.'</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Surat Izin Cuti sesuai kategori</p>
                                <div class="flex gap-2">
                                    <a href="'.$urlSurat.'" target="_blank" style="background-color: rgb(217 119 6); padding: 0.5rem 1rem; border-radius: 0.5rem; color: white; font-weight: bold; text-decoration: none; display: inline-block;">
                                        Download / Preview Surat Izin Cuti
                                    </a>
                                </div>
                            </div>';
                        } else {
                            $html .= '<div class="p-4 bg-gray-50 border rounded-lg dark:bg-gray-800 dark:border-gray-700">
                                <h4 class="font-bold text-lg mb-1">'.$suratIzinName.'</h4>
                                <p class="text-sm text-red-500">Dokumen Surat Izin Cuti belum tersedia.</p>
                            </div>';
                        }
                        
                        $html .= '</div>';
                        
                        return new \Illuminate\Support\HtmlString($html);
                    }),"""

for path in files:
    with open(path, 'r') as f:
        content = f.read()

    # Find the old action
    pattern = re.compile(r"Action::make\('cetak_pdf'\).*?visible\(.*?\),", re.DOTALL)
    if pattern.search(content):
        # because we use backreferences \I in replacement if it is not a raw string, but it is raw string now.
        content = pattern.sub(replacement.replace('\\', '\\\\'), content)
        with open(path, 'w') as f:
            f.write(content)
        print(f"Updated {path}")
    else:
        print(f"Failed to find pattern in {path}")
