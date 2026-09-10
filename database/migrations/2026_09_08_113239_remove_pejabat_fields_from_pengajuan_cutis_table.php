<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropColumn(['keputusan_pejabat', 'alasan_pejabat']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            // Update the enum to remove 'menunggu_pejabat' and 'ditolak_pejabat'
            DB::statement("ALTER TABLE pengajuan_cutis MODIFY status ENUM(
                'menunggu_atasan','disetujui','ditolak_kanit','ditolak_kasubag','perubahan','ditangguhkan',
                'menunggu_kepala_unit','menunggu_kepala_seksi','menunggu_kanit_kepegawaian','menunggu_kasubag_tu',
                'ditolak_kepala_unit','ditolak_kepala_seksi','ditolak_kanit_kepegawaian','ditolak_kasubag_tu'
            ) NOT NULL DEFAULT 'menunggu_atasan'");
        }
    }

    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->enum('keputusan_pejabat', ['disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui'])->nullable();
            $table->text('alasan_pejabat')->nullable();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE pengajuan_cutis MODIFY status ENUM(
                'menunggu_atasan','menunggu_pejabat','disetujui','ditolak_kanit','ditolak_kasubag','ditolak_pejabat','perubahan','ditangguhkan',
                'menunggu_kepala_unit','menunggu_kepala_seksi','menunggu_kanit_kepegawaian','menunggu_kasubag_tu',
                'ditolak_kepala_unit','ditolak_kepala_seksi','ditolak_kanit_kepegawaian','ditolak_kasubag_tu'
            ) NOT NULL DEFAULT 'menunggu_atasan'");
        }
    }
};
