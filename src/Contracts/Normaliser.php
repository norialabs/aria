<?php

declare(strict_types=1);

namespace Noria\Aria\Contracts;

interface Normaliser
{
    public function normalise(string $text): string;
}
