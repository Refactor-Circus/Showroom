<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Flag\Actions\CreateFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Data\CreateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

final class CreateFlagMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', FlagModel::class);
    }

    protected function rules(): array
    {
        return CreateFlagData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $flag = app(CreateFlagAction::class)->execute(CreateFlagData::fromValidated($validated));

        return (new FlagResource($flag))->toStructuredResponse();
    }
}
