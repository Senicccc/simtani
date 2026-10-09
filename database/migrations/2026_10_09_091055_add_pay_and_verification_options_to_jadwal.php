<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jadwal', function (Blueprint $table): void {
            $table->decimal('upah_per_anggota', 15, 2)->default(0);
            $table->boolean('perlu_verifikasi')->default(true);
        });

        DB::table('jadwal')
            ->orderBy('id')
            ->get(['id', 'jenis_pekerjaan_id', 'jumlah_satuan'])
            ->each(function (object $jadwal): void {
                $tarif = (float) DB::table('jenis_pekerjaan')
                    ->where('id', $jadwal->jenis_pekerjaan_id)
                    ->value('tarif_default');
                $jumlahSatuan = max((float) ($jadwal->jumlah_satuan ?? 1), 1);

                DB::table('jadwal')
                    ->where('id', $jadwal->id)
                    ->update(['upah_per_anggota' => round($tarif * $jumlahSatuan, 2)]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table): void {
            $table->dropColumn(['upah_per_anggota', 'perlu_verifikasi']);
        });
    }
};
