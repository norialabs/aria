<?php

declare(strict_types=1);

namespace Noria\Aria\Support;

use Noria\Aria\Contracts\BudgetPolicy;

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
