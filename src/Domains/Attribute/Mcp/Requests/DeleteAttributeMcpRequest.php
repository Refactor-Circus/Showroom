<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Attribute\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Attribute\Actions\DeleteAttributeAction;

final class DeleteAttributeMcpRequest extends AttributeRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->catalogAttribute());
    }

    protected function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $attribute = app(DeleteAttributeAction::class)->execute($this->catalogAttribute());

        return Response::structured(['deleted' => true, 'code' => $attribute->code]);
    }
}
