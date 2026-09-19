<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Support;

use Illuminate\Support\Facades\Config;

class Masker
{
    public function mask(string $text): string
    {
        if (! Config::boolean('aria.masking.enabled', true)) {
            return $text;
        }

        $patterns = Config::array('aria.masking.patterns', []);

        foreach ($patterns as $label => $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            $replaced = preg_replace($pattern, '['.mb_strtoupper((string) $label).' REDACTED]', $text);

            if (is_string($replaced)) {
                $text = $replaced;
            }
        }

        return $text;
    }
}
