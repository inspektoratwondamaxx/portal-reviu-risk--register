<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Komponen HSPK (material/tenaga kerja/peralatan). harga_satuan & subtotal adalah cache hasil
     * perhitungan terakhir — HspkCalculationService yang menjaganya tetap sinkron dengan ssh_items/sbu_items.
     */
    public function up(): void
    {
        Schema::create('hspk_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hspk_id')->constrained('hspk')->cascadeOnDelete();
            $table->string('komponen_type', 20);
            $table->foreignId('ssh_item_id')->nullable()->constrained('ssh_items')->nullOnDelete();
            $table->foreignId('sbu_item_id')->nullable()->constrained('sbu_items')->nullOnDelete();
            $table->string('uraian')->nullable();
            $table->decimal('koefisien', 18, 4)->default(0);
            $table->string('satuan', 30)->nullable();
            $table->decimal('harga_satuan', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index('hspk_id');
            $table->index('ssh_item_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE hspk_components ADD CONSTRAINT hspk_components_type_check CHECK (komponen_type IN ('material','tenaga_kerja','peralatan'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hspk_components');
    }
};
