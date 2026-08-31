<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Master SBU (Standar Biaya Umum): honorarium, perjalanan dinas, konsumsi, dst. */
    public function up(): void
    {
        Schema::create('sbu_items', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('kategori', 30);
            $table->string('uraian');
            $table->string('satuan', 30);
            $table->string('wilayah')->nullable();
            $table->decimal('besaran', 18, 2)->default(0);
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans');
            $table->string('dasar_penetapan')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('kategori');
            $table->index(['status', 'is_active']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE sbu_items ADD CONSTRAINT sbu_items_kategori_check CHECK (kategori IN ('honorarium','perjalanan_dinas','konsumsi','transportasi','akomodasi','lainnya'))");
            DB::statement("ALTER TABLE sbu_items ADD CONSTRAINT sbu_items_status_check CHECK (status IN ('draft','diajukan','verifikasi','disetujui','aktif','ditolak','nonaktif'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sbu_items');
    }
};
