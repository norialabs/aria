<?php

declare(strict_types=1);

namespace NoriaLabs\Aria;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Events\AgentFailed;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Events\EmbeddingsGenerated;
use NoriaLabs\Aria\Console\IndexCommand;
use NoriaLabs\Aria\Contracts\BudgetPolicy;
use NoriaLabs\Aria\Contracts\Normaliser;
use NoriaLabs\Aria\Contracts\Persona;
use NoriaLabs\Aria\Conversations\AriaConversationStore;
use NoriaLabs\Aria\Conversations\Assistant;
use NoriaLabs\Aria\Knowledge\KnowledgeIndex;
use NoriaLabs\Aria\Spend\Budget;
use NoriaLabs\Aria\Spend\RecordRun;
use NoriaLabs\Aria\Support\Masker;
use NoriaLabs\Aria\Support\PlainText;
use NoriaLabs\Aria\Support\Unmetered;

class AriaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/aria.php', 'aria');

        $this->app->bind(BudgetPolicy::class, Unmetered::class);

        $this->app->bind(Normaliser::class, function (Application $app) {
            $normaliser = config('aria.normaliser');

            return is_string($normaliser) && $normaliser !== ''
                ? $app->make($normaliser)
                : $app->make(PlainText::class);
        });

        $this->app->singleton(ConversationStore::class, AriaConversationStore::class);

        $this->app->singleton(Budget::class);
        $this->app->singleton(KnowledgeIndex::class);
        $this->app->singleton(Masker::class);

        $this->app->bind(Assistant::class, fn (Application $app) => new Assistant(
            $app->make(Persona::class),
            $app->make(Budget::class),
            $app->make(Masker::class),
            $app->make(Normaliser::class),
            $app->make(ConversationStore::class),
            self::toolsIn($app),
        ));
    }

    /**
     * A tag resolves to whatever was bound against it, so the tools are
     * narrowed here rather than trusted at the constructor.
     *
     * @return list<object>
     */
    private static function toolsIn(Application $app): array
    {
        $tagged = $app->tagged('aria.tools');

        return array_values(array_filter(
            is_array($tagged) ? $tagged : iterator_to_array($tagged, false),
            is_object(...),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([IndexCommand::class]);

            $this->publishes([
                __DIR__.'/../config/aria.php' => config_path('aria.php'),
            ], 'aria-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'aria-migrations');
        }

        if (config('aria.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        if (config('aria.record_runs', true)) {
            Event::listen(AgentPrompted::class, [RecordRun::class, 'prompted']);
            Event::listen(AgentStreamed::class, [RecordRun::class, 'prompted']);
            Event::listen(AgentFailed::class, [RecordRun::class, 'failed']);
            Event::listen(EmbeddingsGenerated::class, [RecordRun::class, 'embedded']);
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Assistant::class, KnowledgeIndex::class, Budget::class, BudgetPolicy::class, ConversationStore::class];
    }
}
