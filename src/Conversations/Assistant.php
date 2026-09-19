<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Conversations;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Laravel\Ai\Streaming\Events\TextDelta;
use NoriaLabs\Aria\Agents\AriaAgent;
use NoriaLabs\Aria\Contracts\Normaliser;
use NoriaLabs\Aria\Contracts\Persona;
use NoriaLabs\Aria\Exceptions\BudgetExhausted;
use NoriaLabs\Aria\Spend\Budget;
use NoriaLabs\Aria\Support\Masker;

class Assistant
{
    /** @param iterable<int, object> $tools */
    public function __construct(
        private Persona $persona,
        private Budget $budget,
        private Masker $masker,
        private Normaliser $normaliser,
        private ConversationStore $store,
        private iterable $tools = [],
    ) {}

    public function reply(string $message, ?string $conversationId = null, ?object $participant = null): AgentResponse
    {
        $message = $this->masker->mask(trim($message));

        $agent = $this->agent($message, $conversationId, $participant);

        $reserved = $this->reserve($message);

        try {
            $response = $agent->prompt($message, provider: $this->provider());
        } finally {
            $this->budget->refund($reserved);
        }

        $response->text = $this->normaliser->normalise($response->text);

        return $response;
    }

    /**
     * @param  (callable(string): void)|null  $onDelta
     */
    public function stream(string $message, ?string $conversationId = null, ?object $participant = null, ?callable $onDelta = null): StreamableAgentResponse
    {
        $message = $this->masker->mask(trim($message));

        $agent = $this->agent($message, $conversationId, $participant);

        $reserved = $this->reserve($message);

        $response = $agent->stream($message, provider: $this->provider());

        $response->then(fn () => $this->budget->refund($reserved));

        if ($onDelta === null) {
            return $response;
        }

        return $response->each(function (object $event) use ($onDelta): void {
            if ($event instanceof TextDelta) {
                $delta = $this->normaliser->normalise($event->delta);

                if ($delta !== '') {
                    $onDelta($delta);
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function provider(): array
    {
        return [
            Config::string('aria.provider', 'openai') => Config::string('aria.model', ''),
        ];
    }

    private function agent(string $message, ?string $conversationId, ?object $participant): AriaAgent
    {
        $agent = new AriaAgent($this->persona, $this->tools);

        if ($conversationId !== null) {
            return $agent->continue($conversationId, $participant);
        }

        if ($participant !== null) {
            return $agent->forParticipant($participant);
        }

        return $agent->continue(
            $this->store->storeConversation(null, null, Str::limit($message, 50, preserveWords: true)),
        );
    }

    private function reserve(string $prompt): int
    {
        $estimate = $this->budget->estimateFor($prompt);

        if (! $this->budget->reserve($estimate)) {
            throw BudgetExhausted::forPeriod();
        }

        return $estimate;
    }
}
