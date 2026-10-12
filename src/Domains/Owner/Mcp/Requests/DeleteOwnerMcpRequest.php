<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Owner\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Owner\Actions\DeleteOwnerAction;

final class DeleteOwnerMcpRequest extends OwnerRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->owner());
    }

    protected function rules(): array
    {
        return [
            'owner' => ['required', 'string', 'max:191'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $owner = app(DeleteOwnerAction::class)->execute($this->owner());

        return Response::structured(['deleted' => true, 'code' => $owner->code]);
    }
}
