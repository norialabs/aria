<?php

declare(strict_types=1);

namespace Noria\Aria\Contracts;

use Noria\Aria\Knowledge\KnowledgeDocument;

interface KnowledgeSource
{
    /**
     * @return iterable<int, KnowledgeDocument>
     */
    public function documents(): iterable;

    public function corpus(): string;
}
