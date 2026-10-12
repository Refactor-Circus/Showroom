<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Association;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationModel;
use RefactorCircus\Showroom\Domains\Association\Models\AssociationTypeModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\ProductModel\Models\ProductModelModel;

class AssociationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // How the audit log (refactor-circus/keen) names them: a type by the current
        // locale's label, else its code; a link by its type and both ends.
        // Registered only while Showroom audits (see Package::audits()), which
        // is known once every provider has booted.
        $this->app->booted(function (): void {
            if (! $this->package()->audits()) {
                return;
            }

            $this->app->make(AuditHooks::class)
                ->label(AssociationTypeModel::class, fn (AssociationTypeModel $type): string => $type->label())
                ->label(AssociationModel::class, fn (AssociationModel $association): string => $association->type->label().': '
                    .self::end($association->source, $association->source_id).' → '.self::end($association->target, $association->target_id));
        });

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * A product by its identifier, a product model by its code.
     */
    private static function end(?Model $record, string $id): string
    {
        return match (true) {
            $record instanceof ProductModel => $record->identifier,
            $record instanceof ProductModelModel => $record->code,
            default => $id,
        };
    }
}
