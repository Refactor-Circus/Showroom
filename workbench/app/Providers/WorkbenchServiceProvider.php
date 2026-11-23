<?php

namespace Workbench\App\Providers;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Http\Middleware\SignInWorkbenchUser;

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
    }
}
