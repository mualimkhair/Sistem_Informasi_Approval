<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blangko_cutis', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('pengajuan_cuti_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('kabandara_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('alasan')->nullable();
            $table->date('tanggal_keputusan')->nullable();
            
            // Snapshot Kabandara
            $table->string('kabandara_nama')->nullable();
            $table->string('kabandara_nip')->nullable();
            $table->string('kabandara_pangkat')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blangko_cutis');
    }
};
