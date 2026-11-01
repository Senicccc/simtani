<?php

namespace App\Services;

use InvalidArgumentException;

final class SmartScoringService
{
    /**
     * @param  array<int, string>  $order
     * @return array<string, float>
     */
    public function rocWeights(array $order): array
    {
        if ($order === [] || array_values($order)[0] !== 'beban') {
            throw new InvalidArgumentException('Kriteria beban harus berada pada urutan pertama.');
        }

        $requiredCriteria = ['beban', 'jeda', 'kompetensi', 'kehadiran'];
        if (count($order) !== count($requiredCriteria)
            || count(array_unique($order)) !== count($order)
            || array_diff($order, $requiredCriteria) !== []
            || array_diff($requiredCriteria, $order) !== []) {
            throw new InvalidArgumentException('Urutan harus memuat beban, jeda, kompetensi, dan kehadiran tepat satu kali.');
        }

        $numberOfCriteria = count($order);
        $weights = [];

        foreach (array_values($order) as $index => $criterion) {
            $sum = 0.0;
            for ($rank = $index + 1; $rank <= $numberOfCriteria; $rank++) {
                $sum += 1 / $rank;
            }

            $weights[$criterion] = $sum / $numberOfCriteria;
        }

        return $weights;
    }

    /**
     * @param  array<int, array{anggota_id: int, beban: float, jeda: int, kompetensi: int, kehadiran: float}>  $candidates
     * @param  array<string, float>  $weights
     * @return array<int, array<string, mixed>>
     */
    public function rank(array $candidates, array $weights, int $jedaCap = 30): array
    {
        if ($candidates === []) {
            return [];
        }

        if ($jedaCap <= 0) {
            throw new InvalidArgumentException('Batas jeda harus lebih besar dari nol.');
        }

        $loads = array_column($candidates, 'beban');
        $maximumLoad = max($loads);
        $minimumLoad = min($loads);

        $ranked = array_map(function (array $candidate) use ($weights, $jedaCap, $maximumLoad, $minimumLoad): array {
            $u1 = $maximumLoad > $minimumLoad
                ? ($maximumLoad - $candidate['beban']) / ($maximumLoad - $minimumLoad)
                : 1.0;
            $u2 = min(max($candidate['jeda'], 0), $jedaCap) / $jedaCap;
            $u3 = (max(1, min(3, $candidate['kompetensi'])) - 1) / 2;
            $u4 = max(0.0, min(1.0, $candidate['kehadiran']));
            $score = $weights['beban'] * $u1
                + $weights['jeda'] * $u2
                + $weights['kompetensi'] * $u3
                + $weights['kehadiran'] * $u4;

            return $candidate + [
                'utilitas' => compact('u1', 'u2', 'u3', 'u4'),
                'skor' => $score,
            ];
        }, $candidates);

        usort($ranked, static fn (array $first, array $second): int => [
            -round($first['skor'], 12),
            $first['beban'],
            $first['anggota_id'],
        ] <=> [
            -round($second['skor'], 12),
            $second['beban'],
            $second['anggota_id'],
        ]);

        return $ranked;
    }
}
