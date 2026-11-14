<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_masuk_id')->constrained('surat_masuk')->cascadeOnDelete();
            $table->foreignId('dari_user_id')->constrained('users');
            $table->foreignId('kepada_user_id')->constrained('users');
            $table->json('instruksi');
            $table->text('catatan')->nullable();
            $table->date('batas_waktu')->nullable();
            $table->string('status', 20)->default('menunggu'); // menunggu | diproses | selesai
            $table->text('tanggapan')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();

            $table->index(['kepada_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposisi');
    }
};
