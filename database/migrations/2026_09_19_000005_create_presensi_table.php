<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->unique()->constrained('penugasan')->cascadeOnDelete();
            $table->dateTime('waktu_check_in')->nullable();
            $table->dateTime('waktu_check_out')->nullable();
            $table->enum('status', ['hadir', 'tidak_hadir', 'izin'])->default('hadir');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presensi');
    }
};
