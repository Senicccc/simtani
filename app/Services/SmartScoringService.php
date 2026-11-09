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
        if ($order === []) {
            throw new InvalidArgumentException('Urutan kriteria tidak boleh kosong.');
        }

        foreach ($order as $criterion) {
            if (! is_string($criterion)) {
                throw new InvalidArgumentException('Setiap kriteria harus berupa nama yang valid.');
            }
        }

        if (array_values($order)[0] !== 'beban') {
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
        if ($jedaCap <= 0) {
            throw new InvalidArgumentException('Batas jeda harus lebih besar dari nol.');
        }

        $requiredCriteria = ['beban', 'jeda', 'kompetensi', 'kehadiran'];
        if (array_keys($weights) !== $requiredCriteria) {
            $weightCriteria = array_keys($weights);
            sort($weightCriteria);
            $expectedCriteria = $requiredCriteria;
            sort($expectedCriteria);
            if ($weightCriteria !== $expectedCriteria) {
                throw new InvalidArgumentException('Bobot harus memuat beban, jeda, kompetensi, dan kehadiran tepat satu kali.');
            }
        }

        $weightTotal = 0.0;
        foreach ($requiredCriteria as $criterion) {
            $weight = $weights[$criterion] ?? null;
            if (! is_int($weight) && ! is_float($weight)) {
                throw new InvalidArgumentException('Setiap bobot harus berupa angka finite dan tidak negatif.');
            }
            if (! is_finite((float) $weight) || $weight < 0) {
                throw new InvalidArgumentException('Setiap bobot harus berupa angka finite dan tidak negatif.');
            }
            $weightTotal += $weight;
        }

        if (abs($weightTotal - 1.0) > 1e-9) {
            throw new InvalidArgumentException('Jumlah bobot harus sama dengan 1.');
        }

        if ($candidates === []) {
            return [];
        }

        foreach ($candidates as $index => $candidate) {
            if (! is_array($candidate)) {
                throw new InvalidArgumentException("Kandidat ke-{$index} harus berupa array.");
            }

            foreach (['anggota_id', 'beban', 'jeda', 'kompetensi', 'kehadiran'] as $key) {
                if (! array_key_exists($key, $candidate)) {
                    throw new InvalidArgumentException("Kandidat ke-{$index} tidak memiliki nilai {$key}.");
                }
                if (! is_int($candidate[$key]) && ! is_float($candidate[$key])) {
                    throw new InvalidArgumentException("Nilai {$key} pada kandidat ke-{$index} harus numerik.");
                }
                if (! is_finite((float) $candidate[$key])) {
                    throw new InvalidArgumentException("Nilai {$key} pada kandidat ke-{$index} harus finite.");
                }
            }

            if (! is_int($candidate['anggota_id'])
                || ! is_int($candidate['jeda'])
                || ! is_int($candidate['kompetensi'])
                || $candidate['anggota_id'] <= 0
                || $candidate['beban'] < 0
                || $candidate['jeda'] < 0
                || $candidate['jeda'] > $jedaCap
                || $candidate['kompetensi'] < 1
                || $candidate['kompetensi'] > 3
                || $candidate['kehadiran'] < 0
                || $candidate['kehadiran'] > 1) {
                throw new InvalidArgumentException("Nilai kandidat ke-{$index} berada di luar rentang kriteria.");
            }
        }

        $loads = array_column($candidates, 'beban');
        $maximumLoad = max($loads);
        $minimumLoad = min($loads);

        $ranked = array_map(function (array $candidate) use ($weights, $jedaCap, $maximumLoad, $minimumLoad): array {
            $u1 = $maximumLoad > $minimumLoad
                ? ($maximumLoad - $candidate['beban']) / ($maximumLoad - $minimumLoad)
                : 1.0;
            $u2 = $candidate['jeda'] / $jedaCap;
            $u3 = ($candidate['kompetensi'] - 1) / 2;
            $u4 = $candidate['kehadiran'];
            $score = $weights['beban'] * $u1
                + $weights['jeda'] * $u2
                + $weights['kompetensi'] * $u3
                + $weights['kehadiran'] * $u4;

            return array_merge($candidate, [
                'utilitas' => compact('u1', 'u2', 'u3', 'u4'),
                'skor' => $score,
            ]);
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
