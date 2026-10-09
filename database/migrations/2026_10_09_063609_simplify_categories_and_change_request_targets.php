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
        Schema::table('permintaan_perubahan', function (Blueprint $table): void {
            $table->foreignId('target_jadwal_id')->nullable()->after('target_penugasan_id')->constrained('jadwal')->nullOnDelete();
        });

        DB::table('permintaan_perubahan')
            ->whereNotNull('target_penugasan_id')
            ->orderBy('id')
            ->get(['id', 'target_penugasan_id'])
            ->each(function (object $request): void {
                $targetScheduleId = DB::table('penugasan')
                    ->where('id', $request->target_penugasan_id)
                    ->value('jadwal_id');

                if ($targetScheduleId) {
                    DB::table('permintaan_perubahan')
                        ->where('id', $request->id)
                        ->update(['target_jadwal_id' => $targetScheduleId]);
                }
            });

        Schema::table('jenis_pekerjaan', function (Blueprint $table): void {
            $table->dropColumn('status_aktif');
        });

        Schema::table('penugasan', function (Blueprint $table): void {
            $table->text('catatan_perubahan')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jenis_pekerjaan', function (Blueprint $table): void {
            $table->boolean('status_aktif')->default(true);
        });

        Schema::table('penugasan', function (Blueprint $table): void {
            $table->dropColumn('catatan_perubahan');
        });

        Schema::table('permintaan_perubahan', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('target_jadwal_id');
        });
    }
};
