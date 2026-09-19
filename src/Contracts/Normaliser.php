<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Contracts;

interface Normaliser
{
    public function normalise(string $text): string;
}
