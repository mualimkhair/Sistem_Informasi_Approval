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
        Schema::create('seksis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_seksi');
            $table->foreignId('kepala_seksi_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('unit_kerjas', function (Blueprint $table) {
            $table->foreignId('seksi_id')->nullable()->constrained('seksis')->nullOnDelete();
            $table->foreignId('kepala_unit_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('seksi_id')->nullable()->constrained('seksis')->nullOnDelete();
        });

        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->foreignId('seksi_id')->nullable()->constrained('seksis')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropForeign(['seksi_id']);
            $table->dropColumn('seksi_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['seksi_id']);
            $table->dropColumn('seksi_id');
        });

        Schema::table('unit_kerjas', function (Blueprint $table) {
            $table->dropForeign(['kepala_unit_id']);
            $table->dropColumn('kepala_unit_id');
            $table->dropForeign(['seksi_id']);
            $table->dropColumn('seksi_id');
            $table->dropColumn('is_active');
        });

        Schema::dropIfExists('seksis');
    }
};
