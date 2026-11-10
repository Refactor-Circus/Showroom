<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Flag\Actions\ListFlagsAction;
use RefactorCircus\Showroom\Domains\Flag\Data\ListFlagsData;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

final class IndexFlagsRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', FlagModel::class);
    }

    public function rules(): array
    {
        return ListFlagsData::rules();
    }

    public function persist(): JsonResponse
    {
        $flags = app(ListFlagsAction::class)->execute(ListFlagsData::fromValidated($this->validated()));

        return FlagResource::collection($flags)->response();
    }
}
