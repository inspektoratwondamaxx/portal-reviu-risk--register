<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kampungs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kampung', 15)->unique();
            $table->string('nama_kampung', 100);
            $table->string('kecamatan', 100);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kampungs');
    }
};
