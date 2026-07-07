<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saldo_cuti_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pengajuan_cuti_id', 26)->nullable();
            $table->foreign('pengajuan_cuti_id')->references('id')->on('pengajuan_cutis')->cascadeOnDelete();
            $table->string('jenis_cuti'); // tahunan, besar, etc.
            $table->enum('aksi', ['hold', 'release', 'potong']);
            $table->integer('jumlah'); // always positive
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'jenis_cuti']);
            $table->index('pengajuan_cuti_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_cuti_ledgers');
    }
};
