<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Conversations;

use Laravel\Ai\Storage\DatabaseConversationStore;
use NoriaLabs\Aria\Aria;
use NoriaLabs\Aria\Contracts\BudgetPolicy;
use NoriaLabs\Aria\Contracts\KnowledgeSource;

class AriaConversationStore extends DatabaseConversationStore
{
    public function __construct(
        private KnowledgeSource $source,
        private BudgetPolicy $policy,
    ) {
        parent::__construct(Aria::connection());
    }

    public function storeConversation(?string $participantType, string|int|null $participantId, string $title): string
    {
        $conversationId = parent::storeConversation($participantType, $participantId, $title);

        $this->table($this->conversationsTable())
            ->where('id', $conversationId)
            ->update([
                'corpus' => $this->source->corpus(),
                'scope' => $this->policy->scope(),
            ]);

        return $conversationId;
    }

    protected function conversationsTable(): string
    {
        return Aria::table('conversations');
    }

    protected function messagesTable(): string
    {
        return Aria::table('messages');
    }
}
