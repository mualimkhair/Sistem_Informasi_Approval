<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blangko_cutis', function (Blueprint $table) {
            if (!Schema::hasColumn('blangko_cutis', 'kabandara_signature_path')) {
                $table->string('kabandara_signature_path')->nullable()->after('kabandara_jabatan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('blangko_cutis', function (Blueprint $table) {
            if (Schema::hasColumn('blangko_cutis', 'kabandara_signature_path')) {
                $table->dropColumn('kabandara_signature_path');
            }
        });
    }
};
