<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** HSPK Pekerjaan: gabungan komponen material/tenaga-kerja/peralatan jadi satu harga satuan pekerjaan. */
    public function up(): void
    {
        Schema::create('hspk', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('uraian');
            $table->string('jenis_pekerjaan')->nullable();
            $table->string('satuan', 30);
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggarans');
            $table->decimal('harga_satuan', 18, 2)->default(0);
            $table->string('status', 20)->default('aktif');
            $table->boolean('is_active')->default(true);
            $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('uraian');
            $table->index(['status', 'is_active']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE hspk ADD CONSTRAINT hspk_status_check CHECK (status IN ('draft','diajukan','verifikasi','disetujui','aktif','ditolak','nonaktif'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hspk');
    }
};
