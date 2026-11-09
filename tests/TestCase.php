<?php

declare(strict_types=1);

namespace Noria\Aria\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\AiServiceProvider;
use Noria\Aria\AriaServiceProvider;
use Noria\Aria\Contracts\KnowledgeSource;
use Noria\Aria\Contracts\Persona;
use Noria\Aria\Tests\Fixtures\StubPersona;
use Noria\Aria\Tests\Fixtures\StubSource;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AiServiceProvider::class, AriaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        if (($url = env('ARIA_TEST_PG')) !== null) {
            $app['config']->set('database.connections.aria_pg', [
                'driver' => 'pgsql',
                'url' => $url,
                'charset' => 'utf8',
                'prefix' => '',
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ]);
            $app['config']->set('database.default', 'aria_pg');
        } else {
            $app['config']->set('database.default', 'testing');
        }

        $app['config']->set('cache.default', 'array');
        $app['config']->set('ai.caching.embeddings.store', 'array');
        $app['config']->set('aria.pricing', [
            'cheap-model' => ['input' => 100_000, 'output' => 200_000],
            'dear-model' => ['input' => 900_000, 'output' => 3_000_000],
        ]);

        $app->bind(KnowledgeSource::class, StubSource::class);
        $app->bind(Persona::class, StubPersona::class);
    }
}
