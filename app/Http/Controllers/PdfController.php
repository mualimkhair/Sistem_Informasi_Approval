<?php

namespace App\Http\Controllers;

use App\Models\PengajuanCuti;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    public function cetak(PengajuanCuti $pengajuanCuti)
    {
        $pengajuanCuti->load(['user.unitKerja', 'kelompokKerja']);
        
        $pdf = Pdf::loadView('pdf.cetak-cuti', compact('pengajuanCuti'))->setPaper('legal', 'portrait');
        return $pdf->stream('Form-Cuti-'.$pengajuanCuti->user->nip.'.pdf');
    }
}
