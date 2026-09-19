<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Contracts;

interface Persona
{
    public function instructions(): string;

    public function greeting(): ?string;
}
