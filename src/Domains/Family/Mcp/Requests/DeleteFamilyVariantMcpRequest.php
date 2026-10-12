<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Family\Actions\DeleteFamilyVariantAction;

final class DeleteFamilyVariantMcpRequest extends FamilyVariantRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->familyVariant());
    }

    protected function rules(): array
    {
        return [
            'family_variant' => ['required', 'string', 'max:191'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $familyVariant = app(DeleteFamilyVariantAction::class)->execute($this->familyVariant());

        return Response::structured(['deleted' => true, 'code' => $familyVariant->code]);
    }
}
