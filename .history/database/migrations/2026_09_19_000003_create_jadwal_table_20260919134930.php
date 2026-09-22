<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_pekerjaan_id')->constrained('jenis_pekerjaan')->cascadeOnDelete();
            $table->string('nama_kegiatan');
            $table->text('deskripsi')->nullable();
            $table->string('lokasi')->nullable();
            $table->date('tanggal');
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->unsignedInteger('jumlah_orang_dibutuhkan')->default(1);
            $table->decimal('jumlah_satuan', 12, 2)->nullable();
            $table->enum('metode_penjadwalan', ['otomatis', 'manual']);
            $table->enum('status', ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal');
    }
};
