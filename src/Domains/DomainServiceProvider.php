<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains;

use Illuminate\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\Asset\AssetServiceProvider;
use RefactorCircus\Showroom\Domains\Association\AssociationServiceProvider;
use RefactorCircus\Showroom\Domains\Attribute\AttributeServiceProvider;
use RefactorCircus\Showroom\Domains\Category\CategoryServiceProvider;
use RefactorCircus\Showroom\Domains\Channel\ChannelServiceProvider;
use RefactorCircus\Showroom\Domains\ExternalId\ExternalIdServiceProvider;
use RefactorCircus\Showroom\Domains\Family\FamilyServiceProvider;
use RefactorCircus\Showroom\Domains\Flag\FlagServiceProvider;
use RefactorCircus\Showroom\Domains\Owner\OwnerServiceProvider;
use RefactorCircus\Showroom\Domains\Product\ProductServiceProvider;
use RefactorCircus\Showroom\Domains\ProductModel\ProductModelServiceProvider;
use RefactorCircus\Showroom\Domains\Search\SearchServiceProvider;
use RefactorCircus\Showroom\Domains\Transfer\TransferServiceProvider;
use RefactorCircus\Showroom\Domains\Workflow\WorkflowServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * The domain service providers.
     *
     * @var array<int, class-string<ServiceProvider>>
     */
    private array $providers = [
        AssetServiceProvider::class,
        AssociationServiceProvider::class,
        AttributeServiceProvider::class,
        CategoryServiceProvider::class,
        ChannelServiceProvider::class,
        ExternalIdServiceProvider::class,
        FamilyServiceProvider::class,
        FlagServiceProvider::class,
        OwnerServiceProvider::class,
        ProductServiceProvider::class,
        ProductModelServiceProvider::class,
        SearchServiceProvider::class,
        TransferServiceProvider::class,
        WorkflowServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
