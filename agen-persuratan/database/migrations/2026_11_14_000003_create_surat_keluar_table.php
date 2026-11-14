<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_keluar', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('urut');
            $table->string('nomor_agenda', 30);
            $table->string('nomor_surat');
            $table->date('tanggal_surat');
            $table->string('tujuan');
            $table->text('perihal');
            $table->string('sifat', 20)->default('biasa');
            $table->string('klasifikasi', 30)->nullable();
            $table->text('ringkasan')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->unique(['tahun', 'urut']);
            $table->index('tanggal_surat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_keluar');
    }
};
