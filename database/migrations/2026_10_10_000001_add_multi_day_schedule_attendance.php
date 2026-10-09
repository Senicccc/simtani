<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('jadwal', 'tanggal_selesai')) {
            Schema::table('jadwal', function (Blueprint $table): void {
                $table->date('tanggal_selesai')->nullable()->after('tanggal');
            });
        }

        DB::table('jadwal')->whereNull('tanggal_selesai')->update([
            'tanggal_selesai' => DB::raw('tanggal'),
        ]);

        Schema::table('penugasan', function (Blueprint $table): void {
            if (! Schema::hasColumn('penugasan', 'tanggal_mulai')) {
                $table->date('tanggal_mulai')->nullable()->after('anggota_id');
            }

            if (! Schema::hasColumn('penugasan', 'tanggal_selesai')) {
                $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
            }
        });

        if (! Schema::hasColumn('presensi', 'tanggal')) {
            Schema::table('presensi', function (Blueprint $table): void {
                $table->date('tanggal')->nullable()->after('penugasan_id');
            });
        }

        DB::table('presensi')
            ->whereNull('presensi.tanggal')
            ->join('penugasan', 'presensi.penugasan_id', '=', 'penugasan.id')
            ->join('jadwal', 'penugasan.jadwal_id', '=', 'jadwal.id')
            ->select('presensi.id', 'jadwal.tanggal')
            ->get()
            ->each(function (object $presence): void {
                DB::table('presensi')
                    ->where('id', $presence->id)
                    ->update(['tanggal' => $presence->tanggal]);
            });

        $indexes = Schema::getIndexes('presensi');
        $attendanceUniqueIndexExists = collect($indexes)->contains(
            fn (array $index): bool => $index['name'] === 'presensi_penugasan_id_unique',
        );
        $attendanceForeignKey = collect(Schema::getForeignKeys('presensi'))->first(
            fn (array $foreignKey): bool => in_array('penugasan_id', $foreignKey['columns'], true),
        );

        if ($attendanceForeignKey && DB::getDriverName() === 'mysql') {
            Schema::table('presensi', function (Blueprint $table) use ($attendanceForeignKey): void {
                $table->dropForeign($attendanceForeignKey['name']);
            });
        }

        if ($attendanceUniqueIndexExists) {
            Schema::table('presensi', function (Blueprint $table): void {
                $table->dropUnique('presensi_penugasan_id_unique');
            });
        }

        Schema::table('presensi', function (Blueprint $table): void {
            $table->date('tanggal')->nullable(false)->change();
        });

        $indexes = Schema::getIndexes('presensi');
        $dailyUniqueIndexExists = collect($indexes)->contains(
            fn (array $index): bool => $index['unique'] && $index['columns'] === ['penugasan_id', 'tanggal'],
        );

        if (! $dailyUniqueIndexExists) {
            Schema::table('presensi', function (Blueprint $table): void {
                $table->unique(['penugasan_id', 'tanggal']);
            });
        }

        if ($attendanceForeignKey && DB::getDriverName() === 'mysql') {
            Schema::table('presensi', function (Blueprint $table): void {
                $table->foreign('penugasan_id')->references('id')->on('penugasan')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('presensi', function (Blueprint $table): void {
                $table->dropForeign('presensi_penugasan_id_foreign');
            });
        }

        Schema::table('presensi', function (Blueprint $table): void {
            $table->dropUnique(['penugasan_id', 'tanggal']);
            $table->dropColumn('tanggal');
            $table->unique('penugasan_id');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('presensi', function (Blueprint $table): void {
                $table->foreign('penugasan_id')->references('id')->on('penugasan')->cascadeOnDelete();
            });
        }

        Schema::table('penugasan', function (Blueprint $table): void {
            $table->dropColumn(['tanggal_mulai', 'tanggal_selesai']);
        });

        Schema::table('jadwal', function (Blueprint $table): void {
            $table->dropColumn('tanggal_selesai');
        });
    }
};
