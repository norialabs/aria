<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Support;

use NoriaLabs\Aria\Contracts\BudgetPolicy;

final class Unmetered implements BudgetPolicy
{
    public function cap(): ?int
    {
        return null;
    }

    public function scope(): ?string
    {
        return null;
    }
}
