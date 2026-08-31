<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konteks tahun anggaran aktif dipakai seluruh modul (SSH/SBU/HSPK/ASB) sebagai filter default,
     * mengikuti dropdown "Tahun Anggaran" pada header dashboard di desain aplikasi.
     */
    public function up(): void
    {
        Schema::create('tahun_anggarans', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun')->unique();
            $table->string('status', 20)->default('draft');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_active')->default(false);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE tahun_anggarans ADD CONSTRAINT tahun_anggarans_status_check CHECK (status IN ('draft','aktif','tutup'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_anggarans');
    }
};
