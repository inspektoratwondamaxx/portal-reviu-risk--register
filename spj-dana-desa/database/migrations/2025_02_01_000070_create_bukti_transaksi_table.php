<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto bukti transaksi. `path_file` menunjuk lokasi pada object storage S3-compatible
     * (MinIO) sesuai Bab IV.5 — bukan disimpan sebagai BLOB di basis data.
     */
    public function up(): void
    {
        Schema::create('bukti_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->string('path_file', 255);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('diambil_at');
            $table->jsonb('hasil_ocr_raw')->nullable();
            $table->string('status_ocr', 20)->default('belum_diproses');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bukti_transaksi');
    }
};
