<?php

declare(strict_types=1);

namespace NoriaLabs\Aria\Spend;

use Laravel\Ai\Events\AgentFailed;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\EmbeddingsGenerated;
use Laravel\Ai\Prompts\AgentPrompt;
use NoriaLabs\Aria\Aria;
use NoriaLabs\Aria\Contracts\BudgetPolicy;

class RecordRun
{
    private const ERROR_LIMIT = 1_000;

    public function __construct(
        private BudgetPolicy $policy,
        private Budget $budget,
    ) {}

    public function prompted(AgentPrompted $event): void
    {
        $usage = $event->response->usage;
        $model = $event->prompt->model;
        $cost = RunCost::of($model, $usage);

        $this->write([
            'agent' => $event->prompt->agent::class,
            'provider' => $this->providerOf($event->prompt),
            'model' => $model,
            'status' => 'succeeded',
            'prompt_tokens' => $usage->promptTokens,
            'completion_tokens' => $usage->completionTokens,
            'cost_usd_micros' => $cost,
        ]);

        $this->budget->record($cost);
    }

    public function failed(AgentFailed $event): void
    {
        $this->write([
            'agent' => $event->prompt->agent::class,
            'provider' => $this->providerOf($event->prompt),
            'model' => $event->prompt->model,
            'status' => 'failed',
            'error' => mb_substr($event->exception->getMessage(), 0, self::ERROR_LIMIT),
        ]);
    }

    public function embedded(EmbeddingsGenerated $event): void
    {
        $cost = RunCost::ofTokens($event->model, input: $event->response->tokens);

        $this->write([
            'agent' => 'embeddings',
            'provider' => $event->provider->name(),
            'model' => $event->model,
            'status' => 'succeeded',
            'prompt_tokens' => $event->response->tokens,
            'cost_usd_micros' => $cost,
        ]);

        $this->budget->record($cost);
    }

    /** @param array<string, mixed> $attributes */
    private function write(array $attributes): void
    {
        Aria::runModel()::query()->create($attributes + ['scope' => $this->policy->scope()]);
    }

    private function providerOf(AgentPrompt $prompt): string
    {
        return $prompt->provider->name();
    }
}
