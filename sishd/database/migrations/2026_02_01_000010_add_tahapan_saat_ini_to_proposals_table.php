<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Approval berjenjang (Bab 22.3 kajian): Verifikator -> Tim Standar Harga -> Pejabat Berwenang.
     * tahapan_saat_ini menandai tahap mana yang sedang menunggu keputusan selama status masih
     * menunggu_verifikasi; proposal_reviews tetap jadi jejak lengkap tiap tahap yang sudah dilalui.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->string('tahapan_saat_ini', 30)->default('verifikator')->after('status');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_tahapan_check CHECK (tahapan_saat_ini IN ('verifikator','tim_standar_harga','pejabat_berwenang'))");
        }
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('tahapan_saat_ini');
        });
    }
};
