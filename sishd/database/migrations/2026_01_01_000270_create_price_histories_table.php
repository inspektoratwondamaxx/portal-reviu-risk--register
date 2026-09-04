<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Riwayat perubahan harga (Bab 16 & 22.1 kajian) — versioning tiap perubahan harga SSH/SBU/HSPK/ASB. */
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->string('item_type', 10);
            $table->unsignedBigInteger('item_id');
            $table->decimal('harga_lama', 18, 2);
            $table->decimal('harga_baru', 18, 2);
            $table->decimal('persentase', 8, 2)->default(0);
            $table->string('dasar_perubahan')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal');
            $table->timestamps();

            $table->index(['item_type', 'item_id']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
