<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Attribute\Actions\ShowAttributeAction;
use RefactorCircus\Showroom\Domains\Attribute\Resources\AttributeResource;

final class ShowAttributeRequest extends AttributeRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->catalogAttribute());
    }

    public function persist(): JsonResponse
    {
        $attribute = app(ShowAttributeAction::class)->execute($this->catalogAttribute());

        return (new AttributeResource($attribute))->response();
    }
}
