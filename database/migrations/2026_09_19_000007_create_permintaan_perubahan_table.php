<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_perubahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->constrained('penugasan')->cascadeOnDelete();
            $table->foreignId('diajukan_oleh')->constrained('users')->cascadeOnDelete();
            $table->enum('jenis_permintaan', ['tukar', 'sanggah']);
            $table->foreignId('target_penugasan_id')->nullable()->constrained('penugasan')->nullOnDelete();
            $table->text('alasan');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('diproses_at')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_perubahan');
    }
};
