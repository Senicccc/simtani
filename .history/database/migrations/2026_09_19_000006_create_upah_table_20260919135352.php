<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penugasan_id')->unique()->constrained('penugasan')->cascadeOnDelete();
            $table->enum('mode_perhitungan', ['otomatis', 'manual']);
            $table->decimal('tarif_per_satuan', 15, 2)->nullable();
            $table->decimal('jumlah_satuan', 12, 2)->nullable();
            $table->decimal('jumlah_upah', 15, 2);
            $table->enum('status_pembayaran', ['belum_dibayar', 'sudah_dibayar'])->default('belum_dibayar');
            $table->date('tanggal_pembayaran')->nullable();
            $table->foreignId('dibayar_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upah');
    }
};
