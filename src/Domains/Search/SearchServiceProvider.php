<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search;

use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use Laravel\Scout\EngineManager;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\Search\Console\Commands\ReindexProductsCommand;
use RefactorCircus\Showroom\Domains\Search\Contracts\SearchEngine;
use RefactorCircus\Showroom\Domains\Search\Exceptions\UnsupportedSearchException;
use RefactorCircus\Showroom\Domains\Search\Services\DatabaseEngine;
use RefactorCircus\Showroom\Domains\Search\Services\ScoutEngine;
use RefactorCircus\Showroom\Domains\Search\Support\SyncBuffer;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Resolved per use, so the configured engine can change at runtime.
        $this->app->bind(SearchEngine::class, fn (Application $app): SearchEngine => $this->searchEngine($app));

        // Per request or job, so a deferral never leaks into the next one.
        $this->app->scoped(SyncBuffer::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ReindexProductsCommand::class,
        ]);
    }

    /**
     * The search engine named by `showroom.search.engine`. Null picks Scout
     * when laravel/scout is installed, and the database otherwise.
     */
    private function searchEngine(Application $app): SearchEngine
    {
        $engine = $app->make('config')->get('showroom.search.engine')
            ?? (class_exists(EngineManager::class) ? 'scout' : 'database');

        return match ($engine) {
            'database' => new DatabaseEngine,
            'elasticsearch' => throw UnsupportedSearchException::removedEngine('elasticsearch'),
            'scout' => class_exists(EngineManager::class)
                ? new ScoutEngine($app->make(EngineManager::class))
                : throw UnsupportedSearchException::missingPackage('scout', 'laravel/scout'),
            default => is_string($engine) && is_subclass_of($engine, SearchEngine::class)
                ? $app->make($engine)
                : throw new InvalidArgumentException(sprintf('Unknown Showroom search engine [%s].', is_scalar($engine) ? $engine : get_debug_type($engine))),
        };
    }
}
