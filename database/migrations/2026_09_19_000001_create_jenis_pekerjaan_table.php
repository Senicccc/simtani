<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pekerjaan');
            $table->text('deskripsi')->nullable();
            $table->string('satuan')->nullable();
            $table->decimal('tarif_default', 15, 2)->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_pekerjaan');
    }
};
