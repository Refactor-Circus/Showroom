<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom;

use Illuminate\Support\Facades\Blade;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Support\PackageServiceProvider;
use RefactorCircus\Showroom\Atrium\ScreenAccess;
use RefactorCircus\Showroom\Atrium\ShowroomPlugin;
use RefactorCircus\Showroom\Domains\DomainServiceProvider;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Mcp\ShowroomServer;

class ShowroomServiceProvider extends PackageServiceProvider
{
    /**
     * Describe Showroom to the shared refactor-circus/keystone runtime. Every base
     * class finds the package through it, and the helpers below read the
     * `showroom.*` config keys it names.
     */
    protected function definition(): Package
    {
        return Package::make('showroom', 'RefactorCircus\\Showroom')
            ->label('Showroom')
            ->server(ShowroomServer::class)
            ->authorization();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/showroom.php', 'showroom');

        $this->registerPackage();

        $this->app->singleton(Showroom::class);

        $this->app->register(DomainServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cortex is optional: agents get the Showroom tools only when it is loaded.
        $this->registerCortex();

        // Impex is optional too: imports, exports and feeds are its flows.
        $this->app->make(ImpexIntegration::class)->register();

        $this->registerPolicies();

        // Missing records on Showroom's routes answer a plain "Not found.", as its tools do.
        $this->registerNotFoundResponses();

        $this->registerAtriumPlugin(ShowroomPlugin::class);

        $this->registerMcpServer();

        // GET {prefix}/history: Showroom's audit entries, with refactor-circus/keen.
        $this->loadHistoryRoutes();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'showroom');

        // @showroomCan('update', $product) ... @endshowroomCan: the screens'
        // own check, so a control shows only when its action is allowed.
        Blade::if('showroomCan', ScreenAccess::allows(...));

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'showroom');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/showroom.php' => config_path('showroom.php'),
        ], ['showroom', 'showroom-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/showroom'),
        ], ['showroom', 'showroom-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/showroom'),
        ], ['showroom', 'showroom-lang']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['showroom', 'showroom-migrations']);
    }
}
