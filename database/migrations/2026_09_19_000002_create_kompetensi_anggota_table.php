<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kompetensi_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('jenis_pekerjaan_id')->constrained('jenis_pekerjaan')->cascadeOnDelete();
            $table->enum('tingkat_kemampuan', ['pemula', 'menengah', 'mahir']);
            $table->timestamps();

            $table->unique(['anggota_id', 'jenis_pekerjaan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kompetensi_anggota');
    }
};
