<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_spj', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kampung_id')->constrained('kampungs')->cascadeOnDelete();
            $table->smallInteger('tahun_anggaran');
            $table->smallInteger('bulan');
            $table->string('status', 25)->default('proses');
            $table->decimal('saldo_akhir', 15, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kampung_id', 'tahun_anggaran', 'bulan']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE periode_spj ADD CONSTRAINT periode_spj_bulan_check CHECK (bulan BETWEEN 1 AND 12)');
            DB::statement("ALTER TABLE periode_spj ADD CONSTRAINT periode_spj_status_check CHECK (status IN ('proses','diajukan','disetujui_pendamping','disetujui_inspektorat','revisi','final'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_spj');
    }
};
