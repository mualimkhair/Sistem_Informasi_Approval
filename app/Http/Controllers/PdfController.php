<?php

namespace App\Http\Controllers;

use App\Models\PengajuanCuti;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PdfController extends Controller
{
    private function ensureAuthenticated(): \Illuminate\Contracts\Auth\Authenticatable
    {
        $user = Auth::guard('tab')->user();

        if (!$user) {
            abort(401, 'Anda harus login untuk mengakses dokumen ini.');
        }

        return $user;
    }

    public function cetak(PengajuanCuti $pengajuanCuti)
    {
        $user = $this->ensureAuthenticated();

        if ($pengajuanCuti->user_id !== $user->id && !$user->hasRole(['super_admin', 'admin', 'pejabat_berwenang', 'kepala_unit', 'kasubag_tu', 'kanit', 'kasubag', 'kanit_kepegawaian'])) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        $blangko = $pengajuanCuti->blangkoCuti;

        // Auto-recovery for missing Blangko Cuti PDF
        if ($blangko && (!$blangko->file_blangko_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_blangko_path))) {
            \App\Services\CutiService::generateAndSaveFinalDocuments($blangko);
            $blangko->refresh();
        }

        // Serve from storage if generated
        if ($blangko && $blangko->file_blangko_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_blangko_path)) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($blangko->file_blangko_path);
            return response()->file($path);
        }

        abort(404, 'Dokumen Blangko Cuti final belum tersedia.');
    }

    public function cetakSuratIzinCuti(PengajuanCuti $pengajuanCuti)
    {
        $user = $this->ensureAuthenticated();

        if ($pengajuanCuti->user_id !== $user->id && !$user->hasRole(['super_admin', 'admin', 'pejabat_berwenang', 'kepala_unit', 'kasubag_tu', 'kanit', 'kasubag', 'kanit_kepegawaian'])) {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }

        $blangko = $pengajuanCuti->blangkoCuti;

        if ($pengajuanCuti->status === 'ditangguhkan') {
            abort(403, 'Surat Izin Cuti sudah tidak berlaku karena pengajuan telah ditangguhkan.');
        }

        if ($blangko && $blangko->status !== 'disetujui') {
            abort(403, 'Surat Izin Cuti tidak tersedia karena pengajuan ditolak oleh Kabandara.');
        }

        // Auto-recovery for missing Surat Izin Cuti PDF
        if ($blangko && (!$blangko->file_surat_izin_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_surat_izin_path))) {
            \App\Services\CutiService::generateAndSaveFinalDocuments($blangko);
            $blangko->refresh();
        }

        // Serve from storage if generated
        if ($blangko && $blangko->file_surat_izin_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($blangko->file_surat_izin_path)) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($blangko->file_surat_izin_path);
            return response()->file($path);
        }

        abort(404, 'Surat Izin Cuti belum di-generate atau tidak ditemukan.');
    }

    public function cetakBlangko(PengajuanCuti $pengajuanCuti)
    {
        return $this->cetak($pengajuanCuti);
    }
}
