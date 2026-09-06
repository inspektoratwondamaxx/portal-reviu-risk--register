<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inti penyelesaian masalah pemetaan kode lintas-OPD (Bab 10 kajian). Status divalidasi ulang
     * oleh CodeMappingValidationService setiap kali data terkait berubah, bukan dihitung sekali saja.
     */
    public function up(): void
    {
        Schema::create('code_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_code_id')->constrained('asset_codes')->cascadeOnDelete();
            $table->foreignId('account_code_id')->nullable()->constrained('account_codes')->nullOnDelete();
            $table->foreignId('sipd_code_id')->nullable()->constrained('sipd_codes')->nullOnDelete();
            $table->string('status', 20)->default('tidak_ditemukan');
            $table->text('catatan')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE code_mappings ADD CONSTRAINT code_mappings_status_check CHECK (status IN ('valid','belum_rekening','duplikasi','tidak_ditemukan'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('code_mappings');
    }
};
