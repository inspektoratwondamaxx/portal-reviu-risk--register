<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entitas inti transaksi belanja. Kolom `uuid` dipakai sebagai idempotency key sinkronisasi
     * offline dari aplikasi Android (KF-04, Bab VI.1).
     */
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('kampung_id')->constrained('kampungs')->cascadeOnDelete();
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->restrictOnDelete();
            $table->foreignId('kode_rekening_id')->constrained('kode_rekening')->restrictOnDelete();
            $table->date('tanggal_transaksi');
            $table->text('uraian');
            $table->decimal('nominal', 15, 2);
            $table->string('status', 20)->default('draft');
            $table->string('sumber_input', 20)->default('manual');
            $table->boolean('is_flagged')->default(false);
            $table->text('catatan_flag')->nullable();
            $table->boolean('dibuat_offline')->default(false);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kampung_id', 'status']);
            $table->index(['kampung_id', 'tanggal_transaksi']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE transaksis ADD CONSTRAINT transaksis_status_check CHECK (status IN ('draft','terverifikasi','diajukan','disetujui_pendamping','disetujui_inspektorat','revisi','final'))");
            DB::statement("ALTER TABLE transaksis ADD CONSTRAINT transaksis_sumber_input_check CHECK (sumber_input IN ('manual','ocr_ai'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
