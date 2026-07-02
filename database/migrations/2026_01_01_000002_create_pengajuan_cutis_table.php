<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_cutis', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelompok_kerja_id')->nullable()->constrained('kelompok_kerjas')->nullOnDelete();
            $table->enum('jenis_cuti', ['tahunan', 'besar', 'sakit', 'melahirkan', 'alasan_penting', 'diluar_tanggungan_negara']);
            $table->text('alasan_cuti');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('lama_cuti');
            $table->text('alamat_selama_cuti');
            
            $table->enum('status', [
                'menunggu_atasan', 
                'menunggu_pejabat', 
                'disetujui', 
                'ditolak_kanit', 
                'ditolak_kasubag', 
                'ditolak_pejabat', 
                'perubahan', 
                'ditangguhkan'
            ])->default('menunggu_atasan');

            $table->enum('keputusan_kanit', ['disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui'])->nullable();
            $table->text('alasan_kanit')->nullable();
            
            $table->enum('keputusan_kasubag', ['disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui'])->nullable();
            $table->text('alasan_kasubag')->nullable();
            
            $table->enum('keputusan_pejabat', ['disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui'])->nullable();
            $table->text('alasan_pejabat')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_cutis');
    }
};
