<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\DeleteAttributeGroupAction;

final class DeleteAttributeGroupMcpRequest extends AttributeGroupRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->group());
    }

    protected function rules(): array
    {
        return [
            'group' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $group = app(DeleteAttributeGroupAction::class)->execute($this->group());

        return Response::structured(['deleted' => true, 'code' => $group->code]);
    }
}
