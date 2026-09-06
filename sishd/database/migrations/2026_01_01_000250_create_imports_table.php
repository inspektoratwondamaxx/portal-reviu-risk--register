<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 10);
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedInteger('total_baris')->default(0);
            $table->unsignedInteger('sukses')->default(0);
            $table->unsignedInteger('gagal')->default(0);
            $table->string('status', 20)->default('processing');
            $table->json('error_log')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
