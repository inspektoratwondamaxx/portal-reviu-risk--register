<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit log global (Bab IV.4 / KNF-05): mencatat pelaku, waktu, nilai sebelum/sesudah.
     * Append-only — tidak ada update/delete dari aplikasi.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kampung_id')->nullable()->constrained('kampungs')->nullOnDelete();
            $table->string('model_type', 100);
            $table->unsignedBigInteger('model_id');
            $table->string('action', 30);
            $table->jsonb('data_sebelum')->nullable();
            $table->jsonb('data_sesudah')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
