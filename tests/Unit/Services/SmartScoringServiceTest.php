<?php

namespace Tests\Unit\Services;

use App\Services\SmartScoringService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SmartScoringServiceTest extends TestCase
{
    public function test_default_roc_weights_and_hand_calculated_example(): void
    {
        $service = new SmartScoringService;
        $weights = $service->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']);

        self::assertEqualsWithDelta(25 / 48, $weights['beban'], 1e-9);
        self::assertEqualsWithDelta(13 / 48, $weights['jeda'], 1e-9);
        self::assertEqualsWithDelta(7 / 48, $weights['kompetensi'], 1e-9);
        self::assertEqualsWithDelta(3 / 48, $weights['kehadiran'], 1e-9);
        self::assertEqualsWithDelta(1.0, array_sum($weights), 1e-9);

        $ranked = $service->rank([
            ['anggota_id' => 1, 'beban' => 24, 'jeda' => 2, 'kompetensi' => 3, 'kehadiran' => 0.90],
            ['anggota_id' => 2, 'beban' => 12, 'jeda' => 6, 'kompetensi' => 2, 'kehadiran' => 0.80],
            ['anggota_id' => 3, 'beban' => 18, 'jeda' => 10, 'kompetensi' => 1, 'kehadiran' => 1.00],
            ['anggota_id' => 4, 'beban' => 6, 'jeda' => 4, 'kompetensi' => 2, 'kehadiran' => 0.95],
        ], $weights);

        self::assertSame([4, 2, 3, 1], array_column($ranked, 'anggota_id'));
        self::assertEqualsWithDelta(0.2201388889, $ranked[3]['skor'], 1e-10);
        self::assertEqualsWithDelta(0.5243055556, $ranked[1]['skor'], 1e-10);
        self::assertEqualsWithDelta(0.3263888889, $ranked[2]['skor'], 1e-10);
        self::assertEqualsWithDelta(0.6892361111, $ranked[0]['skor'], 1e-10);
    }

    public function test_rejects_invalid_criterion_orders(): void
    {
        $service = new SmartScoringService;

        foreach ([
            ['beban', 'jeda', 'kompetensi'],
            ['beban', 'jeda', 'jeda', 'kehadiran'],
            ['beban', 'jeda', 'kompetensi', 'lainnya'],
            ['jeda', 'beban', 'kompetensi', 'kehadiran'],
            ['beban', ['jeda'], 'kompetensi', 'kehadiran'],
        ] as $order) {
            try {
                $service->rocWeights($order);
                self::fail('Urutan kriteria tidak valid harus ditolak.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function test_empty_and_single_candidate_inputs_are_supported(): void
    {
        $service = new SmartScoringService;
        $weights = $service->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']);

        self::assertSame([], $service->rank([], $weights));
        self::assertSame(5, $service->rank([
            ['anggota_id' => 5, 'beban' => 0, 'jeda' => 30, 'kompetensi' => 1, 'kehadiran' => 1.0],
        ], $weights)[0]['anggota_id']);
    }

    public function test_ties_use_lower_workload_then_member_id(): void
    {
        $ranked = (new SmartScoringService)->rank([
            ['anggota_id' => 1, 'beban' => 5, 'jeda' => 30, 'kompetensi' => 1, 'kehadiran' => 0.0],
            ['anggota_id' => 9, 'beban' => 0, 'jeda' => 0, 'kompetensi' => 1, 'kehadiran' => 0.0],
            ['anggota_id' => 3, 'beban' => 0, 'jeda' => 0, 'kompetensi' => 1, 'kehadiran' => 0.0],
        ], ['beban' => 0.25, 'jeda' => 0.25, 'kompetensi' => 0.25, 'kehadiran' => 0.25]);

        self::assertSame([3, 9, 1], array_column($ranked, 'anggota_id'));
    }

    public function test_rejects_invalid_caps_weights_and_candidate_values(): void
    {
        $service = new SmartScoringService;
        $weights = $service->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']);
        $candidate = ['anggota_id' => 1, 'beban' => 0, 'jeda' => 30, 'kompetensi' => 1, 'kehadiran' => 1.0];

        $this->expectException(InvalidArgumentException::class);
        $service->rank([$candidate], $weights, 0);
    }

    public function test_rejects_weights_that_are_incomplete_or_do_not_sum_to_one(): void
    {
        $service = new SmartScoringService;
        $candidate = ['anggota_id' => 1, 'beban' => 0, 'jeda' => 30, 'kompetensi' => 1, 'kehadiran' => 1.0];

        foreach ([
            ['beban' => 0.5, 'jeda' => 0.25, 'kompetensi' => 0.25],
            ['beban' => 0.5, 'jeda' => 0.25, 'kompetensi' => 0.25, 'kehadiran' => -0.01],
        ] as $weights) {
            try {
                $service->rank([$candidate], $weights);
                self::fail('Bobot tidak lengkap atau jumlahnya bukan 1 harus ditolak.');
            } catch (InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    #[DataProvider('invalidCandidates')]
    public function test_rejects_out_of_range_or_non_finite_candidates(array $candidate): void
    {
        $service = new SmartScoringService;
        $weights = $service->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']);

        $this->expectException(InvalidArgumentException::class);
        $service->rank([$candidate], $weights);
    }

    public static function invalidCandidates(): array
    {
        $valid = ['anggota_id' => 1, 'beban' => 0, 'jeda' => 30, 'kompetensi' => 1, 'kehadiran' => 1.0];

        return [
            'infinite workload' => [['anggota_id' => 1, 'beban' => INF, 'jeda' => 30, 'kompetensi' => 1, 'kehadiran' => 1.0]],
            'negative presence' => [array_replace($valid, ['kehadiran' => -0.1])],
            'competence out of range' => [array_replace($valid, ['kompetensi' => 4])],
            'fractional gap' => [array_replace($valid, ['jeda' => 2.5])],
            'fractional member id' => [array_replace($valid, ['anggota_id' => 1.5])],
        ];
    }
}
