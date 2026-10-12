<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Channel\Actions\UpdateLocaleAction;
use RefactorCircus\Showroom\Domains\Channel\Data\UpdateLocaleData;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

final class UpdateLocaleMcpRequest extends LocaleRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->locale());
    }

    protected function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:100'],
        ] + UpdateLocaleData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $locale = app(UpdateLocaleAction::class)->execute($this->locale(), UpdateLocaleData::fromValidated($validated));

        return (new LocaleResource($locale))->toStructuredResponse();
    }
}
