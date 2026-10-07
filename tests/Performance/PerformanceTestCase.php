<?php

namespace Tests\Performance;

use Tests\TestCase;

abstract class PerformanceTestCase extends TestCase
{
    /**
     * Measure execution time of a callable over multiple iterations.
     *
     * @param callable $fn
     * @param int $iterations
     * @return array{total_ms: float, avg_ms: float, ops_per_sec: float}
     */
    protected function measure(callable $fn, int $iterations = 1000): array
    {
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $fn($i);
        }

        $total = microtime(true) - $start;
        $totalMs = $total * 1000;

        return [
            'total_ms' => round($totalMs, 2),
            'avg_ms' => round($totalMs / $iterations, 4),
            'ops_per_sec' => $iterations > 0 ? round($iterations / $total, 0) : 0,
        ];
    }

    /**
     * Assert that performance is within absolute threshold.
     *
     * @param array{total_ms: float, ops_per_sec: float} $result
     * @param float $thresholdMs
     * @param string $label
     */
    protected function assertPerformanceAbsolute(
        array $result,
        float $thresholdMs,
        string $label = ''
    ): void {
        $suffix = $label !== '' ? " ({$label})" : '';

        expect($result['total_ms'])
            ->toBeLessThanOrEqual($thresholdMs,
                "Performance failed: {$result['total_ms']}ms > {$thresholdMs}ms{$suffix}");
    }

    /**
     * Assert that performance ratio between two results is within bounds.
     *
     * @param array{total_ms: float} $baseline
     * @param array{total_ms: float} $comparison
     * @param float $maxRatio Maximum allowed ratio (e.g., 3.0 means comparison can be up to 3x slower)
     * @param string $label
     */
    protected function assertPerformanceRelative(
        array $baseline,
        array $comparison,
        float $maxRatio = 3.0,
        string $label = ''
    ): void {
        if ($baseline['total_ms'] === 0.0) {
            return;
        }

        $ratio = $comparison['total_ms'] / $baseline['total_ms'];
        $suffix = $label !== '' ? " ({$label})" : '';

        expect($ratio)
            ->toBeLessThanOrEqual($maxRatio,
                "Relative performance failed: ratio {$ratio}x > {$maxRatio}x{$suffix}");
    }
}
