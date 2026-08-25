<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spj_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_spj_id')->constrained('periode_spj')->cascadeOnDelete();
            $table->string('path_pdf', 255);
            $table->integer('versi')->default(1);
            $table->foreignId('generated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spj_dokumen');
    }
};
