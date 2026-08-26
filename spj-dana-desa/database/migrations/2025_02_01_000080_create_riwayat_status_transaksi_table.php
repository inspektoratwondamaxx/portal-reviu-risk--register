<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_status_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->string('status_lama', 20)->nullable();
            $table->string('status_baru', 20);
            $table->text('catatan')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_transaksi');
    }
};
