<?php

declare(strict_types=1);

namespace Noria\Aria\Tests\Fixtures;

use Noria\Aria\Contracts\KnowledgeSource;
use Noria\Aria\Knowledge\KnowledgeDocument;

class StubSource implements KnowledgeSource
{
    /** @var list<KnowledgeDocument> */
    public static array $documents = [];

    public function documents(): iterable
    {
        return self::$documents;
    }

    public function corpus(): string
    {
        return 'stub';
    }
}
