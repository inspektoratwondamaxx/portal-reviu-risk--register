<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak approval berjenjang (Bab 22.3 kajian): Operator -> Verifikator -> Tim Standar Harga
     * -> Pejabat berwenang. Satu proposal bisa punya beberapa baris review lintas tahapan.
     */
    public function up(): void
    {
        Schema::create('proposal_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users');
            $table->string('tahapan', 30)->default('verifikator');
            $table->string('keputusan', 10);
            $table->text('catatan')->nullable();
            $table->timestamp('reviewed_at')->useCurrent();
            $table->timestamps();

            $table->index('proposal_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE proposal_reviews ADD CONSTRAINT proposal_reviews_keputusan_check CHECK (keputusan IN ('setuju','revisi','tolak'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_reviews');
    }
};
