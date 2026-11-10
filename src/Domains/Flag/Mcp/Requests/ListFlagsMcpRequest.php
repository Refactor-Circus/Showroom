<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Flag\Actions\ListFlagsAction;
use RefactorCircus\Showroom\Domains\Flag\Data\ListFlagsData;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

final class ListFlagsMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', FlagModel::class);
    }

    protected function rules(): array
    {
        return ListFlagsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $flags = app(ListFlagsAction::class)->execute(ListFlagsData::fromValidated($validated));

        return FlagResource::collection($flags)->toStructuredResponse();
    }
}
