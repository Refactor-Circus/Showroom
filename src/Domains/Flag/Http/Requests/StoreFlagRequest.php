<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Flag\Actions\CreateFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Data\CreateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

final class StoreFlagRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', FlagModel::class);
    }

    public function rules(): array
    {
        return CreateFlagData::rules();
    }

    public function persist(): JsonResponse
    {
        $flag = app(CreateFlagAction::class)->execute(CreateFlagData::fromValidated($this->validated()));

        return (new FlagResource($flag))->response()->setStatusCode(201);
    }
}
