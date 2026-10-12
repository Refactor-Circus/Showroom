<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Channel\Actions\CreateLocaleAction;
use RefactorCircus\Showroom\Domains\Channel\Data\CreateLocaleData;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

final class CreateLocaleMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', LocaleModel::class);
    }

    protected function rules(): array
    {
        return CreateLocaleData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $locale = app(CreateLocaleAction::class)->execute(CreateLocaleData::fromValidated($validated));

        return (new LocaleResource($locale))->toStructuredResponse();
    }
}
