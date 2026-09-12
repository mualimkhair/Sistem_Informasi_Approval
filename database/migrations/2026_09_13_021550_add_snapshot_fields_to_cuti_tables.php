<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            // Administrasi flow snapshots
            $table->string('kanit_nama')->nullable();
            $table->string('kanit_nip')->nullable();
            $table->string('kanit_pangkat')->nullable();
            $table->string('kanit_jabatan')->nullable();
            $table->dateTime('kanit_tanggal_keputusan')->nullable();

            $table->string('kasubag_nama')->nullable();
            $table->string('kasubag_nip')->nullable();
            $table->string('kasubag_pangkat')->nullable();
            $table->string('kasubag_jabatan')->nullable();
            $table->dateTime('kasubag_tanggal_keputusan')->nullable();

            // Operasional flow snapshots
            $table->string('kepala_unit_nama')->nullable();
            $table->string('kepala_unit_nip')->nullable();
            $table->string('kepala_unit_pangkat')->nullable();
            $table->string('kepala_unit_jabatan')->nullable();
            $table->dateTime('kepala_unit_tanggal_keputusan')->nullable();

            $table->string('kepala_seksi_nama')->nullable();
            $table->string('kepala_seksi_nip')->nullable();
            $table->string('kepala_seksi_pangkat')->nullable();
            $table->string('kepala_seksi_jabatan')->nullable();
            $table->dateTime('kepala_seksi_tanggal_keputusan')->nullable();

            $table->string('kanit_kepegawaian_nama')->nullable();
            $table->string('kanit_kepegawaian_nip')->nullable();
            $table->string('kanit_kepegawaian_pangkat')->nullable();
            $table->string('kanit_kepegawaian_jabatan')->nullable();
            $table->dateTime('kanit_kepegawaian_tanggal_keputusan')->nullable();

            $table->string('kasubag_tu_nama')->nullable();
            $table->string('kasubag_tu_nip')->nullable();
            $table->string('kasubag_tu_pangkat')->nullable();
            $table->string('kasubag_tu_jabatan')->nullable();
            $table->dateTime('kasubag_tu_tanggal_keputusan')->nullable();
        });

        Schema::table('blangko_cutis', function (Blueprint $table) {
            $table->string('kabandara_jabatan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropColumn([
                'kanit_nama', 'kanit_nip', 'kanit_pangkat', 'kanit_jabatan', 'kanit_tanggal_keputusan',
                'kasubag_nama', 'kasubag_nip', 'kasubag_pangkat', 'kasubag_jabatan', 'kasubag_tanggal_keputusan',
                'kepala_unit_nama', 'kepala_unit_nip', 'kepala_unit_pangkat', 'kepala_unit_jabatan', 'kepala_unit_tanggal_keputusan',
                'kepala_seksi_nama', 'kepala_seksi_nip', 'kepala_seksi_pangkat', 'kepala_seksi_jabatan', 'kepala_seksi_tanggal_keputusan',
                'kanit_kepegawaian_nama', 'kanit_kepegawaian_nip', 'kanit_kepegawaian_pangkat', 'kanit_kepegawaian_jabatan', 'kanit_kepegawaian_tanggal_keputusan',
                'kasubag_tu_nama', 'kasubag_tu_nip', 'kasubag_tu_pangkat', 'kasubag_tu_jabatan', 'kasubag_tu_tanggal_keputusan',
            ]);
        });

        Schema::table('blangko_cutis', function (Blueprint $table) {
            $table->dropColumn('kabandara_jabatan');
        });
    }
};
