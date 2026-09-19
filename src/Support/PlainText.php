<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Support;

use NoriaLabs\Aria\Contracts\Normaliser;

final class PlainText implements Normaliser
{
    public function normalise(string $text): string
    {
        return $text;
    }
}
