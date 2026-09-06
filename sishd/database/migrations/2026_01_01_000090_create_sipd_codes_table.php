<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kode referensi format SIPD (sistem penganggaran daerah) tujuan akhir mapping & ekspor. */
    public function up(): void
    {
        Schema::create('sipd_codes', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('uraian');
            $table->string('tipe', 10);
            $table->foreignId('account_code_id')->nullable()->constrained('account_codes')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sipd_codes');
    }
};
