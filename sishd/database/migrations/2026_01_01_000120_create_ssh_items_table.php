<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master SSH (Standar Satuan Harga barang/jasa) — tabel inti "single source of truth"
     * yang dipakai HSPK sebagai sumber harga komponen (Bab 8 & 20 kajian).
     */
    public function up(): void
    {
        Schema::create('ssh_items', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 30)->unique();
            $table->foreignId('asset_code_id')->nullable()->constrained('asset_codes')->nullOnDelete();
            $table->foreignId('account_code_id')->nullable()->constrained('account_codes')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans');
            $table->string('uraian');
            $table->text('spesifikasi')->nullable();
            $table->string('merek')->nullable();
            $table->string('satuan', 30);
            $table->decimal('harga', 18, 2)->default(0);
            $table->string('sumber_harga')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->string('status', 20)->default('aktif');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('uraian');
            $table->index(['status', 'is_active']);
            $table->index('tahun_anggaran_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE ssh_items ADD CONSTRAINT ssh_items_status_check CHECK (status IN ('draft','diajukan','verifikasi','disetujui','aktif','ditolak','nonaktif'))");

            // Similarity matching anti-duplikasi (Bab 14 kajian) via pg_trgm, dipakai
            // DuplicateDetectionService untuk mencari uraian yang mirip, bukan hanya exact match.
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX ssh_items_uraian_trgm_idx ON ssh_items USING gin (uraian gin_trgm_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ssh_items');
    }
};
