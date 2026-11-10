<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Support\PackageServiceProvider;
use RefactorCircus\Showroom\Atrium\ScreenAccess;
use RefactorCircus\Showroom\Atrium\ShowroomPlugin;
use RefactorCircus\Showroom\Domains\DomainServiceProvider;
use RefactorCircus\Showroom\Domains\ExternalId\Models\ExternalIdModel;
use RefactorCircus\Showroom\Domains\ExternalId\Services\ExternalIds;
use RefactorCircus\Showroom\Impex\ImpexIntegration;
use RefactorCircus\Showroom\Mcp\ShowroomServer;
use RefactorCircus\Showroom\Support\RecordLookup;

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

        // One instance per request, never shared across Octane requests.
        $this->app->scoped(ExternalIds::class);

        $this->app->register(DomainServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // A lookup key that is neither `id` nor `slug` fails here, not on the first request.
        $this->app->make(RecordLookup::class)->assertConfigured();

        // Polymorphic columns (the audit log's) store this short name, not the
        // class name. Its rows name their product or owner by those models'
        // own aliases, showroom_product and showroom_owner.
        $this->keepMorphAliases([
            'showroom_external_id' => ExternalIdModel::class,
        ]);

        $this->registerRecordBindings();

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

    /**
     * Bind `{product}` and `{owner}` on Showroom's routes (API and dashboard)
     * through RecordLookup: by the configured key, or the key the request
     * asks for with `?by=`. The binding runs before the request is
     * validated, so an invalid `by` answers 422 here, before the action.
     *
     * A host route with a parameter of the same name is left to Laravel's
     * own binding.
     */
    private function registerRecordBindings(): void
    {
        $router = $this->app->make(Router::class);

        foreach (['product' => 'products', 'owner' => 'owners'] as $parameter => $subject) {
            $router->bind($parameter, function (string $value, Route $route) use ($subject): mixed {
                if (! str_starts_with((string) $route->getControllerClass(), __NAMESPACE__.'\\')) {
                    return $value;
                }

                $by = RecordLookup::by($this->app->make(Request::class)->query('by'));

                return $this->app->make(RecordLookup::class)->resolve($subject, $value, $by);
            });
        }
    }
}
