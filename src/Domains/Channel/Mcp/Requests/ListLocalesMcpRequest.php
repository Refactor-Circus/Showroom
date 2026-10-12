<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Channel\Actions\ListLocalesAction;
use RefactorCircus\Showroom\Domains\Channel\Data\ListLocalesData;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

final class ListLocalesMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', LocaleModel::class);
    }

    protected function rules(): array
    {
        return ListLocalesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $locales = app(ListLocalesAction::class)->execute(ListLocalesData::fromValidated($validated));

        return LocaleResource::collection($locales)->toStructuredResponse();
    }
}
