<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SmartCriteriaService;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

#[Signature('smart:evaluasi {--dari=2026-11-02} {--sampai=} {--csv=}')]
#[Description('Menghitung pemerataan beban dan ringkasan penugasan pada periode tertentu.')]
class SmartScheduleEvaluationCommand extends Command
{
    public function handle(SmartCriteriaService $criteria): int
    {
        $periodStart = $this->parseDate((string) $this->option('dari'));
        $endOption = (string) $this->option('sampai');
        $defaultEnd = $periodStart && today()->lt($periodStart) ? $periodStart->toDateString() : today()->toDateString();
        $periodEnd = $this->parseDate($endOption !== '' ? $endOption : $defaultEnd);

        if ($periodStart === null || $periodEnd === null) {
            $this->error('Tanggal harus menggunakan format YYYY-MM-DD dan merupakan tanggal yang valid.');

            return self::FAILURE;
        }

        if ($periodEnd->lt($periodStart)) {
            $this->error('Tanggal sampai tidak boleh lebih awal daripada tanggal dari.');

            return self::FAILURE;
        }

        $members = User::query()
            ->where('role', 'anggota')
            ->where('status_aktif', true)
            ->orderBy('id')
            ->get(['id', 'nama_lengkap', 'nomor_anggota']);
        $memberIds = $members->modelKeys();
        $workloads = $criteria->workloadsForPeriod($periodStart, $periodEnd, $memberIds);
        $memberStats = [];

        foreach ($members as $member) {
            $memberStats[$member->id] = [
                'nama_lengkap' => $member->nama_lengkap,
                'nomor_anggota' => $member->nomor_anggota,
                'total_jam' => $workloads[$member->id] ?? 0.0,
                'tingkat_kompetensi' => [],
                'penugasan' => ['otomatis' => 0, 'manual' => 0, 'hasil_perubahan' => 0],
                'perubahan' => ['tukar' => 0, 'sanggah' => 0],
            ];
        }

        $assignments = DB::table('penugasan as p')
            ->leftJoin('jadwal as j', 'j.id', '=', 'p.jadwal_id')
            ->leftJoin('kompetensi_anggota as k', function ($join): void {
                $join->on('k.anggota_id', '=', 'p.anggota_id')
                    ->on('k.jenis_pekerjaan_id', '=', 'j.jenis_pekerjaan_id');
            })
            ->whereIn('p.anggota_id', $memberIds)
            ->where('p.status', '!=', 'cancelled')
            ->where(function ($query) use ($periodStart, $periodEnd): void {
                $query->where(function ($overlapping) use ($periodStart, $periodEnd): void {
                    $overlapping
                        ->whereRaw('COALESCE(p.tanggal_mulai, j.tanggal) <= ?', [$periodEnd->toDateString()])
                        ->whereRaw('COALESCE(p.tanggal_selesai, j.tanggal_selesai, j.tanggal) >= ?', [$periodStart->toDateString()]);
                })->orWhereRaw(
                    'COALESCE(p.tanggal_mulai, j.tanggal) > COALESCE(p.tanggal_selesai, j.tanggal_selesai, j.tanggal)',
                )->orWhereNull('j.id');
            })
            ->get([
                'p.anggota_id',
                'p.sumber_penugasan',
                'p.tanggal_mulai as assignment_start',
                'p.tanggal_selesai as assignment_end',
                'j.id as schedule_id',
                'j.tanggal as schedule_start',
                'j.tanggal_selesai as schedule_end',
                'k.tingkat_kemampuan',
            ]);

        $assignmentTotals = ['otomatis' => 0, 'manual' => 0, 'hasil_perubahan' => 0];
        foreach ($assignments as $assignment) {
            if ($assignment->schedule_id === null) {
                throw new InvalidArgumentException('Penugasan tidak memiliki jadwal terkait.');
            }

            $assignmentStart = Carbon::parse($assignment->assignment_start ?? $assignment->schedule_start)->startOfDay();
            $assignmentEnd = Carbon::parse($assignment->assignment_end ?? $assignment->schedule_end ?? $assignment->schedule_start)->startOfDay();

            if ($assignmentStart->gt($assignmentEnd)) {
                throw new InvalidArgumentException('Rentang tanggal penugasan tidak valid.');
            }

            if ($assignmentStart->gt($periodEnd) || $assignmentEnd->lt($periodStart)) {
                continue;
            }

            $memberId = (int) $assignment->anggota_id;
            $source = $assignment->sumber_penugasan;
            $level = match ($assignment->tingkat_kemampuan) {
                'pemula' => 1,
                'menengah' => 2,
                'mahir' => 3,
                default => 1,
            };
            $memberStats[$memberId]['tingkat_kompetensi'][] = $level;

            if (array_key_exists($source, $assignmentTotals)) {
                $memberStats[$memberId]['penugasan'][$source]++;
                $assignmentTotals[$source]++;
            }
        }

        $changeRequests = DB::table('permintaan_perubahan')
            ->whereIn('diajukan_oleh', $memberIds)
            ->whereBetween('created_at', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->get(['diajukan_oleh', 'jenis_permintaan']);
        $changeRequestTotals = ['tukar' => 0, 'sanggah' => 0];

        foreach ($changeRequests as $request) {
            if (array_key_exists($request->jenis_permintaan, $changeRequestTotals)) {
                $memberStats[$request->diajukan_oleh]['perubahan'][$request->jenis_permintaan]++;
                $changeRequestTotals[$request->jenis_permintaan]++;
            }
        }

        $hours = array_column($memberStats, 'total_jam');
        $summary = $this->summarize($hours, $memberStats);
        $this->table(
            [
                'Nomor',
                'Anggota',
                'Total jam C1',
                'Level rata-rata',
                'Jml otomatis (non-cancelled)',
                'Jml manual (non-cancelled)',
                'Jml hasil perubahan (non-cancelled)',
                'Tukar',
                'Sanggah',
            ],
            array_map(static function (array $member): array {
                return [
                    $member['nomor_anggota'],
                    $member['nama_lengkap'],
                    number_format($member['total_jam'], 2, ',', '.'),
                    $member['tingkat_kompetensi'] === [] ? '-' : number_format(array_sum($member['tingkat_kompetensi']) / count($member['tingkat_kompetensi']), 2, ',', '.'),
                    $member['penugasan']['otomatis'],
                    $member['penugasan']['manual'],
                    $member['penugasan']['hasil_perubahan'],
                    $member['perubahan']['tukar'],
                    $member['perubahan']['sanggah'],
                ];
            }, array_values($memberStats)),
        );

        $this->newLine();
        $this->info('Ringkasan '.$periodStart->toDateString().' sampai '.$periodEnd->toDateString());
        $this->line('SD jam populasi: '.number_format($summary['sd_jam'], 4, ',', '.'));
        $this->line('Gini: '.number_format($summary['gini'], 4, ',', '.'));
        $this->line('Rasio maksimum/rata-rata: '.$this->formatOptionalNumber($summary['rasio_maksimum_rata_rata']));
        $this->line('Rata-rata level kompetensi per penugasan: '.$this->formatOptionalNumber($summary['rata_rata_kompetensi']));
        $this->line(
            'Jumlah record penugasan non-cancelled per sumber (semua status selain cancelled; bukan jam C1): '
            .json_encode($assignmentTotals, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        );
        $this->line('Permintaan perubahan per jenis: '.json_encode($changeRequestTotals, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        $csvPath = (string) $this->option('csv');
        if ($csvPath !== '') {
            $this->exportCsv($csvPath, $memberStats, $summary, $assignmentTotals, $changeRequestTotals);
            $this->info('CSV disimpan ke '.$csvPath);
        }

        return self::SUCCESS;
    }

    private function parseDate(string $date): ?Carbon
    {
        try {
            $parsedDate = Carbon::createFromFormat('!Y-m-d', $date);
        } catch (InvalidFormatException) {
            return null;
        }

        return $parsedDate && $parsedDate->format('Y-m-d') === $date ? $parsedDate : null;
    }

    /**
     * @param  array<int, float>  $hours
     * @param  array<int, array<string, mixed>>  $members
     * @return array{sd_jam: float, gini: float, rasio_maksimum_rata_rata: ?float, rata_rata_kompetensi: ?float}
     */
    private function summarize(array $hours, array $members): array
    {
        $count = count($hours);
        $mean = $count === 0 ? 0.0 : array_sum($hours) / $count;
        $variance = $count === 0
            ? 0.0
            : array_sum(array_map(static fn (float $value): float => ($value - $mean) ** 2, $hours)) / $count;
        $pairwiseDifferences = 0.0;

        foreach ($hours as $first) {
            foreach ($hours as $second) {
                $pairwiseDifferences += abs($first - $second);
            }
        }

        $competencyLevels = [];
        foreach ($members as $member) {
            $competencyLevels = [...$competencyLevels, ...$member['tingkat_kompetensi']];
        }

        return [
            'sd_jam' => sqrt($variance),
            'gini' => $mean === 0.0 || $count === 0 ? 0.0 : $pairwiseDifferences / (2 * $count ** 2 * $mean),
            'rasio_maksimum_rata_rata' => $mean === 0.0 || $count === 0 ? null : max($hours) / $mean,
            'rata_rata_kompetensi' => $competencyLevels === [] ? null : array_sum($competencyLevels) / count($competencyLevels),
        ];
    }

    private function formatOptionalNumber(?float $value): string
    {
        return $value === null ? 'N/A' : number_format($value, 4, ',', '.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $members
     * @param  array<string, int|float|null>  $summary
     * @param  array<string, int>  $assignmentTotals
     * @param  array<string, int>  $changeRequestTotals
     */
    private function exportCsv(
        string $path,
        array $members,
        array $summary,
        array $assignmentTotals,
        array $changeRequestTotals,
    ): void {
        $file = fopen($path, 'w');
        if ($file === false) {
            throw new RuntimeException('Tidak dapat membuka lokasi CSV untuk ditulis.');
        }

        if (fwrite($file, "\xEF\xBB\xBF") !== 3) {
            fclose($file);
            throw new RuntimeException('Gagal menulis penanda encoding CSV.');
        }

        $this->writeCsvRow($file, [
            'nomor_anggota',
            'nama_lengkap',
            'total_jam_C1',
            'rata_rata_level_kompetensi',
            'jumlah_record_penugasan_non_cancelled_otomatis',
            'jumlah_record_penugasan_non_cancelled_manual',
            'jumlah_record_penugasan_non_cancelled_hasil_perubahan',
            'tukar',
            'sanggah',
        ]);
        foreach ($members as $member) {
            $this->writeCsvRow($file, [
                $member['nomor_anggota'],
                $member['nama_lengkap'],
                $member['total_jam'],
                $member['tingkat_kompetensi'] === [] ? '' : array_sum($member['tingkat_kompetensi']) / count($member['tingkat_kompetensi']),
                $member['penugasan']['otomatis'],
                $member['penugasan']['manual'],
                $member['penugasan']['hasil_perubahan'],
                $member['perubahan']['tukar'],
                $member['perubahan']['sanggah'],
            ]);
        }

        $this->writeCsvRow($file, []);
        $this->writeCsvRow($file, ['metric', 'value']);
        foreach ($summary as $metric => $value) {
            $this->writeCsvRow($file, [$metric, $value ?? 'N/A']);
        }
        foreach ($assignmentTotals as $source => $count) {
            $this->writeCsvRow($file, ['jumlah_record_penugasan_non_cancelled_'.$source, $count]);
        }
        foreach ($changeRequestTotals as $type => $count) {
            $this->writeCsvRow($file, ['perubahan_'.$type, $count]);
        }

        if (! fclose($file)) {
            throw new RuntimeException('Gagal menyelesaikan penulisan CSV.');
        }
    }

    /**
     * @param  resource  $file
     * @param  array<int, mixed>  $values
     */
    private function writeCsvRow(mixed $file, array $values): void
    {
        if (fputcsv($file, $values) === false) {
            throw new RuntimeException('Gagal menulis baris CSV.');
        }
    }
}