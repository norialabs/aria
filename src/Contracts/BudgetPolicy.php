<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Contracts;

interface BudgetPolicy
{
    public function cap(): ?int;

    public function scope(): ?string;
}
