<?php

declare(strict_types=1);

namespace Noria\Aria\Contracts;

interface Persona
{
    public function instructions(): string;

    public function greeting(): ?string;
}
