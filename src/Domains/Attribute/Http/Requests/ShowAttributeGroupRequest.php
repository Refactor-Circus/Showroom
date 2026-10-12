<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ShowAttributeGroupAction;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeGroupResource;

final class ShowAttributeGroupRequest extends AttributeGroupRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->group());
    }

    public function persist(): JsonResponse
    {
        $group = app(ShowAttributeGroupAction::class)->execute($this->group());

        return (new AttributeGroupResource($group))->response();
    }
}
