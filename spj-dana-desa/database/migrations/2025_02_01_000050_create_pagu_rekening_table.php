<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagu_rekening', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->cascadeOnDelete();
            $table->foreignId('kode_rekening_id')->constrained('kode_rekening')->restrictOnDelete();
            $table->decimal('pagu_anggaran', 15, 2);
            $table->timestamps();

            $table->unique(['kegiatan_id', 'kode_rekening_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagu_rekening');
    }
};
