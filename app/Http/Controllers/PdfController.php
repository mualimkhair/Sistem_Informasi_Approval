<?php

namespace App\Http\Controllers;

use App\Models\PengajuanCuti;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function cetak(PengajuanCuti $pengajuanCuti)
    {
        // Authorization check
        $user = auth()->user();
        if ($pengajuanCuti->user_id !== $user->id && !$user->hasRole(['super_admin', 'admin', 'pejabat_berwenang', 'kepala_unit', 'kasubag_tu', 'kanit', 'kasubag', 'kanit_kepegawaian'])) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        $blangko = $pengajuanCuti->blangkoCuti;

        // Serve from storage if generated
        if ($blangko && $blangko->file_blangko_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_blangko_path)) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($blangko->file_blangko_path);
            return response()->file($path);
        }

        abort(404, 'Dokumen Blangko Cuti final belum tersedia.');
    }

    public function cetakSuratIzinCuti(PengajuanCuti $pengajuanCuti)
    {
        // Authorization check: only owner or admin/pejabat can view
        $user = auth()->user();
        if ($pengajuanCuti->user_id !== $user->id && !$user->hasRole(['super_admin', 'admin', 'pejabat_berwenang', 'kepala_unit', 'kasubag_tu', 'kanit', 'kasubag', 'kanit_kepegawaian'])) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        $blangko = $pengajuanCuti->blangkoCuti;
        
        // Serve from storage if generated
        if ($blangko && $blangko->file_surat_izin_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_surat_izin_path)) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($blangko->file_surat_izin_path);
            return response()->file($path);
        }

        abort(404, 'Surat Izin Cuti belum di-generate atau tidak ditemukan.');
    }

    public function cetakBlangko(PengajuanCuti $pengajuanCuti)
    {
        // Delegate to cetak since it uses the single source of truth
        return $this->cetak($pengajuanCuti);
    }
}
