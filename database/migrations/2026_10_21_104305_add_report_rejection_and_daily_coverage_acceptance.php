<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penugasan', function (Blueprint $table): void {
            $table->enum('status', ['assigned', 'in_progress', 'waiting_verification', 'completed', 'rejected', 'cancelled'])->change();
        });

        Schema::table('presensi', function (Blueprint $table): void {
            $table->boolean('coverage_accepted')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table): void {
            $table->dropColumn('coverage_accepted');
        });

        DB::table('penugasan')
            ->where('status', 'rejected')
            ->update(['status' => 'cancelled']);

        Schema::table('penugasan', function (Blueprint $table): void {
            $table->enum('status', ['assigned', 'in_progress', 'waiting_verification', 'completed', 'cancelled'])->change();
        });
    }
};
