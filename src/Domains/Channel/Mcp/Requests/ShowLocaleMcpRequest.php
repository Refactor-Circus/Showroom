<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Channel\Actions\ShowLocaleAction;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

final class ShowLocaleMcpRequest extends LocaleRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->locale());
    }

    protected function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $locale = app(ShowLocaleAction::class)->execute($this->locale());

        return (new LocaleResource($locale))->toStructuredResponse();
    }
}
