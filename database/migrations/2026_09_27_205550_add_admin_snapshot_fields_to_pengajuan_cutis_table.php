<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            if (!Schema::hasColumn('pengajuan_cutis', 'admin_nama')) {
                $table->string('admin_nama')->nullable();
            }
            if (!Schema::hasColumn('pengajuan_cutis', 'admin_nip')) {
                $table->string('admin_nip')->nullable();
            }
            if (!Schema::hasColumn('pengajuan_cutis', 'admin_signature_path')) {
                $table->string('admin_signature_path')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            if (Schema::hasColumn('pengajuan_cutis', 'admin_nama')) {
                $table->dropColumn('admin_nama');
            }
            if (Schema::hasColumn('pengajuan_cutis', 'admin_nip')) {
                $table->dropColumn('admin_nip');
            }
            if (Schema::hasColumn('pengajuan_cutis', 'admin_signature_path')) {
                $table->dropColumn('admin_signature_path');
            }
        });
    }
};