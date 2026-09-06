<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak setiap kali HSPK dihitung ulang otomatis akibat perubahan harga SSH/SBU sumber
     * (Bab 8 kajian: "kalau harga semen berubah di master SSH, sistem menghitung ulang HSPK otomatis").
     */
    public function up(): void
    {
        Schema::create('hspk_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hspk_id')->constrained('hspk')->cascadeOnDelete();
            $table->decimal('harga_sebelum', 18, 2)->default(0);
            $table->decimal('harga_sesudah', 18, 2)->default(0);
            $table->decimal('selisih', 18, 2)->default(0);
            $table->decimal('persentase', 8, 2)->default(0);
            $table->string('pemicu')->nullable();
            $table->string('pemicu_type', 30)->nullable();
            $table->unsignedBigInteger('pemicu_id')->nullable();
            $table->foreignId('dihitung_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dihitung_pada')->useCurrent();
            $table->timestamps();

            $table->index('hspk_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hspk_analysis');
    }
};
