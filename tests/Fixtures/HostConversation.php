<?php

declare(strict_types=1);

namespace Noria\Aria\Tests\Fixtures;

use Noria\Aria\Models\Conversation;

class HostConversation extends Conversation
{
    public function label(): string
    {
        return 'host';
    }
}
