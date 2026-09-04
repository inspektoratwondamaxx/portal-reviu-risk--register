<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usulan OPD (Bab 11-12 kajian): OPD tidak boleh ubah master langsung, semua lewat proposal
     * dengan alur menunggu_verifikasi -> revisi/ditolak/disetujui.
     */
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_usulan', 40)->unique();
            $table->foreignId('opd_id')->constrained('opds');
            $table->string('jenis_usulan', 10);
            $table->string('tipe_perubahan', 20)->default('baru');
            $table->string('status', 20)->default('draft');
            $table->text('alasan_usulan')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('verifikator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diajukan_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'jenis_usulan']);
            $table->index('opd_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_jenis_check CHECK (jenis_usulan IN ('ssh','sbu','hspk','asb'))");
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_tipe_check CHECK (tipe_perubahan IN ('baru','perubahan','nonaktif'))");
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('draft','menunggu_verifikasi','revisi','disetujui','ditolak'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
