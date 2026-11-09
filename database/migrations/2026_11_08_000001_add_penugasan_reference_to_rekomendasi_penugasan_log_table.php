<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekomendasi_penugasan_log', function (Blueprint $table): void {
            $table->foreignId('penugasan_id')
                ->nullable()
                ->after('anggota_id')
                ->constrained('penugasan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rekomendasi_penugasan_log', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('penugasan_id');
        });
    }
};
