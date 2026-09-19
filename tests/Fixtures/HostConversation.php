<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Tests\Fixtures;

use NoriaLabs\Aria\Models\Conversation;

class HostConversation extends Conversation
{
    public function label(): string
    {
        return 'host';
    }
}
