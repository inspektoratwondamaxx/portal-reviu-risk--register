<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Variabel formula ASB. nilai bisa manual atau ditarik otomatis dari ssh_items/hspk/sbu_items
     * (sumber_tipe+sumber_id) sehingga formula tetap "hidup" saat harga sumber berubah — bukan hard-coded
     * (Bab 9 kajian: "formula ASB sebaiknya dibuat parameterized, bukan hard-coded").
     */
    public function up(): void
    {
        Schema::create('asb_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asb_id')->constrained('asb')->cascadeOnDelete();
            $table->string('kode_variabel', 60);
            $table->string('label');
            $table->decimal('nilai', 18, 4)->default(0);
            $table->string('satuan', 30)->nullable();
            $table->string('sumber_tipe', 20)->default('manual');
            $table->unsignedBigInteger('sumber_id')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['asb_id', 'kode_variabel']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE asb_variables ADD CONSTRAINT asb_variables_sumber_check CHECK (sumber_tipe IN ('manual','ssh_item','hspk','sbu_item'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asb_variables');
    }
};
