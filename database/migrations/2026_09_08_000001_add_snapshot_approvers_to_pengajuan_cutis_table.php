<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            // Snapshot identitas Kanit (operasional: Kanit Kepegawaian; administrasi: Kanit)
            $table->string('kanit_nama', 255)->nullable();
            $table->string('kanit_nip', 18)->nullable();
            $table->string('kanit_pangkat', 255)->nullable();
            $table->string('kanit_jabatan', 255)->nullable();
            $table->dateTime('kanit_tanggal_keputusan')->nullable();

            // Snapshot identitas Kasubag (operasional: Kasubag TU; administrasi: Kasubag)
            $table->string('kasubag_nama', 255)->nullable();
            $table->string('kasubag_nip', 18)->nullable();
            $table->string('kasubag_pangkat', 255)->nullable();
            $table->string('kasubag_jabatan', 255)->nullable();
            $table->dateTime('kasubag_tanggal_keputusan')->nullable();

            // Snapshot identitas Pejabat Berwenang (administrasi)
            $table->string('pejabat_nama', 255)->nullable();
            $table->string('pejabat_nip', 18)->nullable();
            $table->string('pejabat_pangkat', 255)->nullable();
            $table->string('pejabat_jabatan', 255)->nullable();
            $table->dateTime('pejabat_tanggal_keputusan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropColumn([
                'kanit_nama', 'kanit_nip', 'kanit_pangkat', 'kanit_jabatan', 'kanit_tanggal_keputusan',
                'kasubag_nama', 'kasubag_nip', 'kasubag_pangkat', 'kasubag_jabatan', 'kasubag_tanggal_keputusan',
                'pejabat_nama', 'pejabat_nip', 'pejabat_pangkat', 'pejabat_jabatan', 'pejabat_tanggal_keputusan',
            ]);
        });
    }
};
