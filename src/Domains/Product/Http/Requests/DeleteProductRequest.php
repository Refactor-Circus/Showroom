<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Product\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Product\Actions\DeleteProductAction;
use RefactorCircus\Showroom\Support\RecordLookup;

final class DeleteProductRequest extends ProductRequest
{
    public function authorize(): bool
    {
        return $this->allows('delete', $this->product());
    }

    public function rules(): array
    {
        return RecordLookup::rules();
    }

    public function persist(): JsonResponse
    {
        app(DeleteProductAction::class)->execute($this->product());

        return new JsonResponse(null, 204);
    }
}
