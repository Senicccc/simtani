<?php

namespace Tests\Unit;

use App\Services\SmartScoringService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SmartScoringServiceTest extends TestCase
{
    public function test_roc_weights_follow_rank_order_centroid(): void
    {
        $weights = (new SmartScoringService)->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']);

        $this->assertEqualsWithDelta(25 / 48, $weights['beban'], 1e-9);
        $this->assertEqualsWithDelta(13 / 48, $weights['jeda'], 1e-9);
        $this->assertEqualsWithDelta(7 / 48, $weights['kompetensi'], 1e-9);
        $this->assertEqualsWithDelta(3 / 48, $weights['kehadiran'], 1e-9);
        $this->assertEqualsWithDelta(1.0, array_sum($weights), 1e-9);
    }

    public function test_hand_calculated_example_scores_utilities_and_order(): void
    {
        $service = new SmartScoringService;
        $fixture = json_decode(
            file_get_contents(dirname(__DIR__).'/Fixtures/smart_contoh_tangan.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $weights = $service->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']);
        $ranked = $service->rank($fixture['candidates'], $weights);

        $byLabel = array_column($ranked, null, 'label');
        $this->assertSame($fixture['expected_order'], array_column($ranked, 'label'));
        foreach ($fixture['expected_scores'] as $label => $expectedScore) {
            $this->assertEqualsWithDelta($expectedScore, $byLabel[$label]['skor'], 1e-4);
            $this->assertEqualsWithDelta(
                $fixture['expected_utilities'][$label],
                array_values($byLabel[$label]['utilitas']),
                1e-4,
            );
        }
    }

    public function test_equal_loads_get_full_load_utility(): void
    {
        $ranked = (new SmartScoringService)->rank([
            ['anggota_id' => 1, 'beban' => 8.0, 'jeda' => 5, 'kompetensi' => 2, 'kehadiran' => 1.0],
            ['anggota_id' => 2, 'beban' => 8.0, 'jeda' => 10, 'kompetensi' => 1, 'kehadiran' => 1.0],
        ], (new SmartScoringService)->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']));

        $this->assertSame(1.0, $ranked[0]['utilitas']['u1']);
        $this->assertSame(1.0, $ranked[1]['utilitas']['u1']);
    }

    public function test_one_candidate_can_be_ranked(): void
    {
        $ranked = (new SmartScoringService)->rank([
            ['anggota_id' => 5, 'beban' => 0.0, 'jeda' => 30, 'kompetensi' => 1, 'kehadiran' => 1.0],
        ], (new SmartScoringService)->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']));

        $this->assertCount(1, $ranked);
        $this->assertSame(1.0, $ranked[0]['utilitas']['u1']);
    }

    public function test_ties_are_broken_by_load_then_member_id(): void
    {
        $ranked = (new SmartScoringService)->rank([
            ['anggota_id' => 7, 'beban' => 50.0, 'jeda' => 10, 'kompetensi' => 2, 'kehadiran' => 0.5],
            ['anggota_id' => 9, 'beban' => 49.99999999999999, 'jeda' => 10, 'kompetensi' => 2, 'kehadiran' => 0.5],
            ['anggota_id' => 2, 'beban' => 50.0, 'jeda' => 10, 'kompetensi' => 2, 'kehadiran' => 0.5],
            ['anggota_id' => 1, 'beban' => 0.0, 'jeda' => 10, 'kompetensi' => 2, 'kehadiran' => 0.5],
            ['anggota_id' => 10, 'beban' => 100.0, 'jeda' => 10, 'kompetensi' => 2, 'kehadiran' => 0.5],
        ], (new SmartScoringService)->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']));

        $this->assertSame([1, 9, 2, 7, 10], array_column($ranked, 'anggota_id'));
        $this->assertSame(
            round($ranked[1]['skor'], 12),
            round($ranked[2]['skor'], 12),
        );
    }

    public function test_beban_must_be_the_first_criterion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SmartScoringService)->rocWeights(['jeda', 'beban', 'kompetensi', 'kehadiran']);
    }
}
