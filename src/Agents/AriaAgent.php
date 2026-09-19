<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Agents;

use Illuminate\Support\Facades\Config;
use Laravel\Ai\Concerns\RemembersConversations as RemembersConversationsTrait;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\RemembersConversations;
use Laravel\Ai\Promptable;
use NoriaLabs\Aria\Contracts\Persona;
use NoriaLabs\Aria\Tools\KnowledgeSearch;

class AriaAgent implements Agent, HasTools, RemembersConversations
{
    use Promptable;
    use RemembersConversationsTrait;

    /** @param iterable<int, object> $extraTools */
    public function __construct(
        private Persona $persona,
        private iterable $extraTools = [],
    ) {}

    public function instructions(): string
    {
        return $this->persona->instructions();
    }

    /** @return iterable<int, object> */
    public function tools(): iterable
    {
        yield app(KnowledgeSearch::class);

        foreach ($this->extraTools as $tool) {
            yield $tool;
        }
    }

    public function maxSteps(): ?int
    {
        return Config::integer('aria.limits.max_steps', 6);
    }

    public function maxTokens(): ?int
    {
        return Config::integer('aria.limits.max_tokens', 1500);
    }

    public function timeout(): int
    {
        return Config::integer('aria.limits.timeout', 60);
    }

    protected function maxConversationMessages(): int
    {
        return Config::integer('aria.history', 20);
    }
}
