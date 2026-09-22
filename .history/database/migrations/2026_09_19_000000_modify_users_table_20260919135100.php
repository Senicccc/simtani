<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nomor_anggota')->unique()->after('id');
            $table->string('nama_lengkap')->after('nomor_anggota');
            $table->string('foto')->nullable()->after('nama_lengkap');
            $table->string('nomor_wa')->nullable()->after('foto');
            $table->string('email')->nullable()->change();
            $table->string('password')->after('email');
            $table->string('role')->default('anggota')->after('password');
            $table->boolean('status_aktif')->default(true)->after('role');
            $table->text('alamat')->nullable()->after('status_aktif');
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['nomor_anggota', 'nama_lengkap', 'foto', 'nomor_wa', 'role', 'status_aktif', 'alamat']);
        });
    }
};
