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
            $table->decimal('total_upah', 15, 2)->default(0)->after('upah_per_anggota');
        });

        DB::table('jadwal')->update([
            'total_upah' => DB::raw('upah_per_anggota * jumlah_orang_dibutuhkan'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal', function (Blueprint $table): void {
            $table->dropColumn('total_upah');
        });
    }
};
