<?php

declare(strict_types=1);

namespace Noria\Aria\Tests\Fixtures;

use Noria\Aria\Contracts\Normaliser;

class Shouty implements Normaliser
{
    public function normalise(string $text): string
    {
        return mb_strtoupper($text);
    }
}
