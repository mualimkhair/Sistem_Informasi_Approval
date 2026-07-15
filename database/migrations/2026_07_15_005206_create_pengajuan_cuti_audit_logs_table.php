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
        Schema::create('pengajuan_cuti_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('pengajuan_cuti_id', 26);
            $table->foreign('pengajuan_cuti_id')->references('id')->on('pengajuan_cutis')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('changes');
            $table->timestamps();
            $table->index('pengajuan_cuti_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan_cuti_audit_logs');
    }
};
