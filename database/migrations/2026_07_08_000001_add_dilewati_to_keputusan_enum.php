<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE pengajuan_cutis MODIFY keputusan_kanit ENUM('disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui', 'dilewati') NULL");
        DB::statement("ALTER TABLE pengajuan_cutis MODIFY keputusan_kasubag ENUM('disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui', 'dilewati') NULL");
        DB::statement("ALTER TABLE pengajuan_cutis MODIFY keputusan_pejabat ENUM('disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui', 'dilewati') NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE pengajuan_cutis MODIFY keputusan_kanit ENUM('disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui') NULL");
        DB::statement("ALTER TABLE pengajuan_cutis MODIFY keputusan_kasubag ENUM('disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui') NULL");
        DB::statement("ALTER TABLE pengajuan_cutis MODIFY keputusan_pejabat ENUM('disetujui', 'perubahan', 'ditangguhkan', 'tidak_disetujui') NULL");
    }
};
