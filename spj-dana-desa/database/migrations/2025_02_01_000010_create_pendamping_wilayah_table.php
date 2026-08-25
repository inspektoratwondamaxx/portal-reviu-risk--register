<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penugasan pendamping desa/kecamatan ke kampung binaan (many-to-many).
     * Dasar scoping akses role "pendamping" per Bab VI.7 kajian teknis.
     */
    public function up(): void
    {
        Schema::create('pendamping_wilayah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kampung_id')->constrained('kampungs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'kampung_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendamping_wilayah');
    }
};
