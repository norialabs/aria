<?php

declare(strict_types=1);

namespace Noria\Aria\Tests\Fixtures;

use Noria\Aria\Contracts\Persona;

class StubPersona implements Persona
{
    public function instructions(): string
    {
        return 'You are a test assistant.';
    }

    public function greeting(): ?string
    {
        return null;
    }
}
