<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Bukti digital survei (Bab 22.4 kajian): foto toko, foto daftar harga, dokumen penawaran. */
    public function up(): void
    {
        Schema::create('price_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_survey_id')->constrained('price_surveys')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('jenis_bukti', 30)->default('lainnya');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_evidence');
    }
};
