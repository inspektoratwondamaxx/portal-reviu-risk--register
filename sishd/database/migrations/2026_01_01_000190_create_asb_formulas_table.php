<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ekspresi formula per ASB, mis. "{luas_bangunan} * {standar_biaya_per_m2}". Dievaluasi oleh
     * SafeFormulaEvaluator (parser aritmatika sendiri, bukan eval()) terhadap asb_variables.
     */
    public function up(): void
    {
        Schema::create('asb_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asb_id')->unique()->constrained('asb')->cascadeOnDelete();
            $table->text('ekspresi');
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asb_formulas');
    }
};
