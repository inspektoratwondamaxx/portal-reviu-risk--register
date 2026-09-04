<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->nullable();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('kecamatan', 60)->nullable();
            $table->string('kelurahan', 60)->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('kontak_person')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
