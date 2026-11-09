<?php

declare(strict_types=1);

namespace Noria\Aria\Contracts;

interface BudgetPolicy
{
    public function cap(): ?int;

    public function scope(): ?string;
}
