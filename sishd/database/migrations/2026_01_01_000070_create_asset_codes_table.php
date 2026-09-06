<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kode Aset/Barang, mis. 1.3.01.01, dipetakan ke Kelompok Barang. */
    public function up(): void
    {
        Schema::create('asset_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_group_id')->nullable()->constrained('asset_groups')->nullOnDelete();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_codes');
    }
};
