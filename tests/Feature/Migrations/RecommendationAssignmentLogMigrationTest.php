<?php

namespace Tests\Feature\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RecommendationAssignmentLogMigrationTest extends TestCase
{
    public function test_penugasan_reference_migration_can_be_reversed(): void
    {
        Schema::create('penugasan', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('rekomendasi_penugasan_log', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('anggota_id');
        });

        $migration = require base_path('database/migrations/2026_11_08_000001_add_penugasan_reference_to_rekomendasi_penugasan_log_table.php');
        $migration->up();

        self::assertTrue(Schema::hasColumn('rekomendasi_penugasan_log', 'penugasan_id'));
        self::assertTrue(collect(Schema::getForeignKeys('rekomendasi_penugasan_log'))->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['penugasan_id']
                && $foreignKey['foreign_table'] === 'penugasan'
                && $foreignKey['on_delete'] === 'set null',
        ));
        self::assertTrue(collect(Schema::getColumns('rekomendasi_penugasan_log'))->contains(
            fn (array $column): bool => $column['name'] === 'penugasan_id' && $column['nullable'],
        ));

        DB::table('penugasan')->insert(['id' => 77]);
        $logId = DB::table('rekomendasi_penugasan_log')->insertGetId([
            'anggota_id' => 1,
            'penugasan_id' => 77,
        ]);
        DB::table('penugasan')->where('id', 77)->delete();
        self::assertNull(DB::table('rekomendasi_penugasan_log')->where('id', $logId)->value('penugasan_id'));

        $migration->down();

        self::assertFalse(Schema::hasColumn('rekomendasi_penugasan_log', 'penugasan_id'));
    }
}
