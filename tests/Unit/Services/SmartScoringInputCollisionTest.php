<?php

namespace Tests\Unit\Services;

use App\Services\SmartScoringService;
use PHPUnit\Framework\TestCase;

class SmartScoringInputCollisionTest extends TestCase
{
    public function test_calculated_score_and_utilities_override_conflicting_candidate_keys(): void
    {
        $candidate = [
            'anggota_id' => 1,
            'beban' => 0,
            'jeda' => 30,
            'kompetensi' => 1,
            'kehadiran' => 1.0,
            'skor' => 99.0,
            'utilitas' => ['forged' => 1.0],
        ];
        $weights = (new SmartScoringService)->rocWeights(['beban', 'jeda', 'kompetensi', 'kehadiran']);

        $ranked = (new SmartScoringService)->rank([$candidate], $weights)[0];

        self::assertEqualsWithDelta($weights['beban'] + $weights['jeda'] + $weights['kehadiran'], $ranked['skor'], 1e-12);
        self::assertEquals(['u1' => 1.0, 'u2' => 1.0, 'u3' => 0.0, 'u4' => 1.0], $ranked['utilitas']);
    }
}
