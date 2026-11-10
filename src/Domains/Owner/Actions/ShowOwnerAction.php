<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Actions;

use Illuminate\Database\Eloquent\Collection;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerShowingActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Events\OwnerShownActionEvent;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;

final class ShowOwnerAction
{
    public function __construct(private readonly Flags $flags) {}

    public function execute(OwnerModel $owner): OwnerModel
    {
        OwnerShowingActionEvent::dispatch($owner);

        $result = $this->perform($owner);

        OwnerShownActionEvent::dispatch($result);

        return $result;
    }

    private function perform(OwnerModel $owner): OwnerModel
    {
        $owner->load(['type', 'parent', 'children.type', 'assets', 'externalIds'])->loadCount(['products', 'productModels']);
        $this->flags->load(new Collection([$owner]));

        return $owner->setRelation('chain', $owner->chain()->load('type'));
    }
}
