<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_masuk', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('urut');
            $table->string('nomor_agenda', 30);
            $table->string('nomor_surat');
            $table->date('tanggal_surat');
            $table->date('tanggal_terima');
            $table->string('asal_surat');
            $table->text('perihal');
            $table->string('sifat', 20)->default('biasa'); // biasa | penting | segera | rahasia
            $table->string('klasifikasi', 30)->nullable();   // kode klasifikasi arsip
            $table->text('ringkasan')->nullable();
            $table->string('status', 20)->default('baru');   // baru | didisposisi | selesai
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->unique(['tahun', 'urut']);
            $table->index('tanggal_terima');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_masuk');
    }
};
