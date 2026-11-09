<?php

namespace Tests\Feature\Seeders;

use App\Models\JenisPekerjaan;
use App\Models\Jadwal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmartDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_dataset_is_fixed_safe_to_rerun_and_within_the_research_period(): void
    {
        $admin = User::factory()->admin()->create([
            'nama_lengkap' => 'Admin existing',
            'password' => Hash::make('keep-existing-password'),
        ]);
        User::factory()->create([
            'nomor_anggota' => 'ANG-001',
            'nama_lengkap' => 'Anggota existing',
        ]);
        User::factory()->inactive()->create(['nomor_anggota' => 'ANG-009']);
        JenisPekerjaan::query()->create([
            'nama_pekerjaan' => 'Panen',
            'deskripsi' => 'Deskripsi existing',
            'created_by' => $admin->id,
        ]);

        app(DatabaseSeeder::class)->run();

        self::assertSame('Admin existing', $admin->fresh()->nama_lengkap);
        self::assertTrue(Hash::check('keep-existing-password', $admin->fresh()->password));
        self::assertSame('Anggota existing', User::query()->where('nomor_anggota', 'ANG-001')->value('nama_lengkap'));
        self::assertFalse((bool) User::query()->where('nomor_anggota', 'ANG-009')->value('status_aktif'));
        self::assertSame('Deskripsi existing', JenisPekerjaan::query()->where('nama_pekerjaan', 'Panen')->value('deskripsi'));
        self::assertSame(25, User::query()->where('role', 'anggota')->where('status_aktif', true)
            ->whereIn('nomor_anggota', array_merge(
                array_map(static fn (int $number): string => sprintf('ANG-%03d', $number), range(1, 8)),
                array_map(static fn (int $number): string => sprintf('ANG-%03d', $number), range(10, 26)),
            ))->count());

        $schedules = Jadwal::query()->where('nama_kegiatan', 'like', 'SIMULASI SMART-ROC%')->get();
        self::assertCount(44, $schedules);
        self::assertSame('2026-11-02', $schedules->min(fn (Jadwal $schedule): string => $schedule->tanggal->toDateString()));
        self::assertSame('2026-11-15', $schedules->max(fn (Jadwal $schedule): string => ($schedule->tanggal_selesai ?? $schedule->tanggal)->toDateString()));
        self::assertSame(2, $schedules->filter(
            fn (Jadwal $schedule): bool => $schedule->tanggal_selesai->gt($schedule->tanggal),
        )->count());
        self::assertSame(2, $schedules->filter(
            fn (Jadwal $schedule): bool => $schedule->waktu_mulai === null && $schedule->waktu_selesai === null,
        )->count());

        foreach (range(2, 15) as $day) {
            $date = sprintf('2026-11-%02d', $day);
            $dailyScheduleCount = $schedules->filter(
                fn (Jadwal $schedule): bool => $schedule->tanggal->toDateString() <= $date
                    && ($schedule->tanggal_selesai ?? $schedule->tanggal)->toDateString() >= $date,
            )->count();
            self::assertGreaterThanOrEqual(3, $dailyScheduleCount);
            self::assertLessThanOrEqual(8, $dailyScheduleCount);
        }

        $competencyCounts = DB::table('kompetensi_anggota')
            ->select('tingkat_kemampuan', DB::raw('COUNT(*) as total'))
            ->groupBy('tingkat_kemampuan')
            ->pluck('total', 'tingkat_kemampuan')
            ->all();
        self::assertSame(['mahir' => 31, 'menengah' => 56, 'pemula' => 38], $competencyCounts);
        self::assertEqualsCanonicalizing(
            ['hadir', 'izin', 'tidak_hadir'],
            DB::table('presensi')->distinct()->orderBy('status')->pluck('status')->all(),
        );

        $counts = [
            Jadwal::query()->count(),
            DB::table('penugasan')->count(),
            DB::table('kompetensi_anggota')->count(),
            DB::table('presensi')->count(),
            DB::table('upah')->count(),
        ];
        app(DatabaseSeeder::class)->run();
        self::assertSame($counts, [
            Jadwal::query()->count(),
            DB::table('penugasan')->count(),
            DB::table('kompetensi_anggota')->count(),
            DB::table('presensi')->count(),
            DB::table('upah')->count(),
        ]);
    }
}
