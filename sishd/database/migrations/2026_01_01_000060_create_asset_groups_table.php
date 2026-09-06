<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kelompok Barang (mis. "Material", "Peralatan") — level teratas mapping kode
     * (Bab 10 kajian: Kode Aset -> Kelompok Barang -> Kode Rekening -> BAS -> Kode SIPD).
     */
    public function up(): void
    {
        Schema::create('asset_groups', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_groups');
    }
};
