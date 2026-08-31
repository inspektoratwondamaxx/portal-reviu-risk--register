<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** ASB (Analisis Standar Belanja) untuk menilai kewajaran biaya kegiatan. */
    public function up(): void
    {
        Schema::create('asb', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama_kegiatan');
            $table->string('kelompok_kegiatan')->nullable();
            $table->string('satuan_variabel', 30)->nullable();
            $table->decimal('batas_minimal', 18, 2)->nullable();
            $table->decimal('batas_maksimal', 18, 2)->nullable();
            $table->decimal('hasil_perhitungan', 18, 2)->default(0);
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans');
            $table->string('status', 20)->default('aktif');
            $table->boolean('is_active')->default(true);
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('nama_kegiatan');
            $table->index(['status', 'is_active']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE asb ADD CONSTRAINT asb_status_check CHECK (status IN ('draft','diajukan','verifikasi','disetujui','aktif','ditolak','nonaktif'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asb');
    }
};
