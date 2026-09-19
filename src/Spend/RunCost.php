<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Spend;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\Data\Usage;

final class RunCost
{
    private const PER_MILLION = 1_000_000;

    public static function of(string $model, Usage $usage): int
    {
        return self::ofTokens(
            $model,
            input: $usage->promptTokens,
            output: $usage->completionTokens + $usage->reasoningTokens,
            cacheWrite: $usage->cacheWriteInputTokens,
            cacheRead: $usage->cacheReadInputTokens,
        );
    }

    public static function ofTokens(
        string $model,
        int $input = 0,
        int $output = 0,
        int $cacheWrite = 0,
        int $cacheRead = 0,
    ): int {
        $table = config('aria.pricing');
        $prices = is_array($table) ? ($table[$model] ?? null) : null;

        if (! is_array($prices)) {
            Log::error('Aria: model missing from the price list, charged at the ceiling rate.', [
                'model' => $model,
            ]);

            $prices = self::ceiling($table);
        }

        return self::priced($input, $prices['input'] ?? 0)
            + self::priced($output, $prices['output'] ?? 0)
            + self::priced($cacheWrite, $prices['cache_write'] ?? 0)
            + self::priced($cacheRead, $prices['cache_read'] ?? 0);
    }

    private static function priced(int $tokens, mixed $perMillionMicros): int
    {
        return intdiv($tokens * (int) $perMillionMicros, self::PER_MILLION);
    }

    /**
     * @return array<string, int>
     */
    private static function ceiling(mixed $table): array
    {
        $ceiling = ['input' => 0, 'output' => 0, 'cache_write' => 0, 'cache_read' => 0];

        foreach (is_array($table) ? $table : [] as $prices) {
            foreach ($ceiling as $direction => $highest) {
                $ceiling[$direction] = max($highest, (int) (is_array($prices) ? ($prices[$direction] ?? 0) : 0));
            }
        }

        return $ceiling;
    }
}
