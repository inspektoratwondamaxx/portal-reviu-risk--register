<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori berjenjang (mis. Material Konstruksi > Semen & Perekat) dipakai filter pencarian
     * publik maupun form Tambah SSH/SBU.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('kode', 20)->nullable();
            $table->string('nama');
            $table->string('jenis', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
