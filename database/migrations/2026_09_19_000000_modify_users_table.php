<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'nomor_anggota')) {
            return;
        }
    }

    public function down(): void
    {
        // no-op; schema is created in the base users migration.
    }
};
