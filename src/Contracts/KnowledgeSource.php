<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Contracts;

use NoriaLabs\Aria\Knowledge\KnowledgeDocument;

interface KnowledgeSource
{
    /**
     * @return iterable<int, KnowledgeDocument>
     */
    public function documents(): iterable;

    public function corpus(): string;
}
