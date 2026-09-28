<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pengajuan = App\Models\PengajuanCuti::first();
if ($pengajuan) {
    if (!$pengajuan->user->saldoCuti) {
        $saldo = new App\Models\SaldoCuti();
        $saldo->user_id = $pengajuan->user_id;
        $pengajuan->user->setRelation('saldoCuti', $saldo);
    }
    
    $html = \Illuminate\Support\Facades\Blade::compileString(file_get_contents('/tmp/cetak-blangko-exact.blade.php'));
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML(view()->file('/tmp/cetak-blangko-exact.blade.php', ['pengajuanCuti' => $pengajuan])->render())->setPaper('a4', 'portrait');
    file_put_contents('/tmp/test-output-exact.pdf', $pdf->output());
    echo "PDF generated at /tmp/test-output-exact.pdf\n";
    system('pdfinfo /tmp/test-output-exact.pdf | grep Pages');
}
