<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Discriminator: which approval flow applies (administrasi = legacy, operasional = 4-stage)
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->enum('tipe_aliran', ['administrasi', 'operasional'])->default('administrasi')->after('status');
        });

        // 4-stage operasional decision + reason columns
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->enum('keputusan_kepala_unit', ['disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui', 'dilewati'])->nullable();
            $table->text('alasan_kepala_unit')->nullable();
            $table->enum('keputusan_kepala_seksi', ['disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui', 'dilewati'])->nullable();
            $table->text('alasan_kepala_seksi')->nullable();
            $table->enum('keputusan_kanit_kepegawaian', ['disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui', 'dilewati'])->nullable();
            $table->text('alasan_kanit_kepegawaian')->nullable();
            $table->enum('keputusan_kasubag_tu', ['disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui', 'dilewati'])->nullable();
            $table->text('alasan_kasubag_tu')->nullable();
        });

        // Snapshot columns for the 4 operasional approvers
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->foreignId('kepala_unit_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kepala_seksi_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kanit_kepegawaian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kasubag_tu_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // Extend the status enum with the operasional statuses.
        // SQLite ignores enum value sets (columns are TEXT), so this is safe on the
        // dev/test connection; on MySQL it performs the required MODIFY.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE pengajuan_cutis MODIFY status ENUM(
                'menunggu_atasan','menunggu_pejabat','disetujui','ditolak_kanit','ditolak_kasubag','ditolak_pejabat','perubahan','ditangguhkan',
                'menunggu_kepala_unit','menunggu_kepala_seksi','menunggu_kanit_kepegawaian','menunggu_kasubag_tu',
                'ditolak_kepala_unit','ditolak_kepala_seksi','ditolak_kanit_kepegawaian','ditolak_kasubag_tu'
            ) NOT NULL DEFAULT 'menunggu_atasan'");
        }
    }

    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kepala_unit_id');
            $table->dropConstrainedForeignId('kepala_seksi_id');
            $table->dropConstrainedForeignId('kanit_kepegawaian_id');
            $table->dropConstrainedForeignId('kasubag_tu_id');
        });

        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropColumn([
                'keputusan_kepala_unit', 'alasan_kepala_unit',
                'keputusan_kepala_seksi', 'alasan_kepala_seksi',
                'keputusan_kanit_kepegawaian', 'alasan_kanit_kepegawaian',
                'keputusan_kasubag_tu', 'alasan_kasubag_tu',
                'tipe_aliran',
            ]);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE pengajuan_cutis MODIFY status ENUM(
                'menunggu_atasan','menunggu_pejabat','disetujui','ditolak_kanit','ditolak_kasubag','ditolak_pejabat','perubahan','ditangguhkan'
            ) NOT NULL DEFAULT 'menunggu_atasan'");
        }
    }
};
