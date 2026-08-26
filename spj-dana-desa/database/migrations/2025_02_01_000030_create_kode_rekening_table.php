<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_rekening', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20);
            $table->string('uraian', 200);
            $table->string('jenis_belanja', 20);
            $table->smallInteger('tahun_anggaran');
            $table->timestamps();

            $table->unique(['kode', 'tahun_anggaran']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE kode_rekening ADD CONSTRAINT kode_rekening_jenis_belanja_check CHECK (jenis_belanja IN ('pegawai','barang_jasa','modal','tak_terduga'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kode_rekening');
    }
};
