<?php

declare(strict_types=1);

namespace Noria\Aria\Support;

use Noria\Aria\Contracts\Normaliser;

final class PlainText implements Normaliser
{
    public function normalise(string $text): string
    {
        return $text;
    }
}
