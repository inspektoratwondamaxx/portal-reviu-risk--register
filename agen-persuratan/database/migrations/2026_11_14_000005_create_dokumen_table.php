<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->morphs('dokumenable');               // surat_masuk / surat_keluar
            $table->string('nama_asli');
            $table->string('path');                       // lokasi relatif pada disk "arsip"
            $table->string('mime', 100);
            $table->unsignedBigInteger('ukuran');
            $table->char('hash', 64)->index();            // SHA-256, untuk deteksi duplikat
            $table->longText('teks_ocr')->nullable();     // diisi pada Fase 2 (OCR)
            $table->foreignId('diunggah_oleh')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};
