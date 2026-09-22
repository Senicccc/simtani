<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal')->cascadeOnDelete();
            $table->foreignId('anggota_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('original_anggota_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['assigned','in_progress','waiting_verification','completed','cancelled']);
            $table->enum('sumber_penugasan', ['otomatis', 'manual', 'hasil_perubahan']);
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('catatan_penyelesaian')->nullable();
            $table->string('bukti_selesai')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasan');
    }
};
