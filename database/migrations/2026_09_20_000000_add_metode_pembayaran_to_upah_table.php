<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upah', function (Blueprint $table): void {
            $table->string('metode_pembayaran')->nullable()->after('status_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('upah', function (Blueprint $table): void {
            $table->dropColumn('metode_pembayaran');
        });
    }
};
