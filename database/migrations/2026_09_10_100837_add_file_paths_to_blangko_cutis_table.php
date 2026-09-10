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
        Schema::table('blangko_cutis', function (Blueprint $table) {
            $table->string('file_blangko_path')->nullable();
            $table->string('file_surat_izin_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blangko_cutis', function (Blueprint $table) {
            $table->dropColumn(['file_blangko_path', 'file_surat_izin_path']);
        });
    }
};
