<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Owner\Actions\ShowOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Resources\OwnerResource;

final class ShowOwnerMcpRequest extends OwnerRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->owner());
    }

    protected function rules(): array
    {
        return [
            'owner' => ['required', 'string', 'max:191'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $owner = app(ShowOwnerAction::class)->execute($this->owner());

        return (new OwnerResource($owner))->toStructuredResponse();
    }
}
