<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldo_cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('saldo_n')->default(0);
            $table->integer('saldo_n1')->default(0);
            $table->integer('saldo_n2')->default(0);
            $table->integer('saldo_cuti_besar')->default(0);
            $table->integer('saldo_cuti_sakit')->default(0);
            $table->integer('saldo_cuti_melahirkan')->default(0);
            $table->integer('saldo_cuti_alasan_penting')->default(0);
            $table->year('tahun_berjalan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_cutis');
    }
};
