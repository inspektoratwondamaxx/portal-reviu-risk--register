<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master bidang kegiatan APBDes mengikuti struktur Siskeudes V2.0 (mis. "1. Penyelenggaraan
     * Pemerintahan Desa"), disertai tahun_anggaran agar dapat diperbarui tiap awal tahun tanpa
     * migrasi besar (KNF-09).
     */
    public function up(): void
    {
        Schema::create('bidang_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10);
            $table->string('nama_bidang', 150);
            $table->smallInteger('tahun_anggaran');
            $table->timestamps();

            $table->unique(['kode', 'tahun_anggaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bidang_kegiatan');
    }
};
