<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kampung_id')->constrained('kampungs')->cascadeOnDelete();
            $table->foreignId('bidang_kegiatan_id')->constrained('bidang_kegiatan')->restrictOnDelete();
            $table->string('nama_kegiatan', 200);
            $table->smallInteger('tahun_anggaran');
            $table->decimal('pagu_total', 15, 2);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kampung_id', 'tahun_anggaran']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan');
    }
};
