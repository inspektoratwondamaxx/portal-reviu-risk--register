<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot yang merangkai transaksi final ke periode BKU bulanannya (Bab IV.2: "data final
     * tersimpan dan otomatis dirangkai menjadi BKU").
     */
    public function up(): void
    {
        Schema::create('periode_spj_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_spj_id')->constrained('periode_spj')->cascadeOnDelete();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->integer('urutan_bku')->nullable();
            $table->timestamps();

            $table->unique(['periode_spj_id', 'transaksi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_spj_transaksi');
    }
};
