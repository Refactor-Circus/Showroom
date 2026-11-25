<?php

namespace Workbench\App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Server;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Http\Middleware\SignInWorkbenchUser;
use Workbench\App\OpenApi\ResourceResponses;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Serve demo assets from the public disk (linked by the build), with
        // host-relative URLs so they load on whatever port `serve` picks.
        config()->set('showroom.media.disk', 'public');
        config()->set('filesystems.disks.public.url', '/storage');

        // Scramble, which writes openapi.json, reads only the workbench's
        // config file: every key is set there, whichever provider merges
        // first.
        config()->set('scramble', array_replace(
            (array) config('scramble', []),
            require dirname(__DIR__, 2).'/config/scramble.php',
        ));

        // Scramble reads model columns when their tables exist, so a migrated
        // workbench database would change the export. `scramble:export`
        // always reads an empty one, as tests/OpenApi does.
        if ($this->app->runningConsoleCommand('scramble:export')) {
            config()->set('database.connections.openapi', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
            config()->set('database.default', 'openapi');
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Keep the workbench user signed in whatever URL is opened first.
        $this->callAfterResolving(HttpKernel::class, function (HttpKernel $kernel): void {
            if ($kernel instanceof Kernel) {
                $kernel->appendMiddlewareToGroup('web', SignInWorkbenchUser::class);
            }
        });

        $this->configureOpenApi();
    }

    /**
     * Name each operation after its route (`showroom.stats.show`), write the
     * success responses Scramble cannot infer through `persist()`, and serve
     * the document from the relative `/showroom`, so no host or port is
     * baked into openapi.json.
     */
    private function configureOpenApi(): void
    {
        Scramble::configure()
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                $operation->setOperationId((string) $routeInfo->route->getName());
            })
            ->withOperationTransformers(ResourceResponses::class)
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->servers = [Server::make('/'.config('scramble.api_path'))];
            });
    }
}
