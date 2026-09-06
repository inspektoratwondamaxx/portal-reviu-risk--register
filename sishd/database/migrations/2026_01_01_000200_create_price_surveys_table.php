<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Survei harga lapangan — dasar penetapan/perubahan harga SSH (Bab 15 kajian). */
    public function up(): void
    {
        Schema::create('price_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ssh_item_id')->nullable()->constrained('ssh_items')->nullOnDelete();
            $table->string('uraian_barang');
            $table->text('spesifikasi')->nullable();
            $table->string('merek')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('lokasi')->nullable();
            $table->date('tanggal_survei');
            $table->decimal('harga', 18, 2);
            $table->foreignId('surveyor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('ssh_item_id');
            $table->index('tanggal_survei');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_surveys');
    }
};
