<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kode Rekening Belanja / Bagan Akun Standar (BAS), berjenjang (akun>kelompok>jenis>obyek>rincian). */
    public function up(): void
    {
        Schema::create('account_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('account_codes')->nullOnDelete();
            $table->string('kode', 30)->unique();
            $table->string('uraian');
            $table->string('jenis_belanja', 40)->nullable();
            $table->unsignedTinyInteger('level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_codes');
    }
};
