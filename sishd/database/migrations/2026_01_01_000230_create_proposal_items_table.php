<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Data barang/harga yang diusulkan disimpan sebagai JSON (data_usulan) agar satu struktur tabel
     * menampung usulan SSH/SBU/HSPK/ASB sekaligus. kemiripan menyimpan hasil DuplicateDetectionService
     * (Bab 14 kajian) yang ditampilkan sebagai peringatan "DATA SERUPA DITEMUKAN" di form usulan.
     */
    public function up(): void
    {
        Schema::create('proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->string('item_type', 10);
            $table->unsignedBigInteger('existing_item_id')->nullable();
            $table->json('data_usulan');
            $table->json('kemiripan')->nullable();
            $table->unsignedBigInteger('created_item_id')->nullable();
            $table->timestamps();

            $table->index(['item_type', 'existing_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_items');
    }
};
