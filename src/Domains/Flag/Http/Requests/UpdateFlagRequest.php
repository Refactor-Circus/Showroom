<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Flag\Actions\UpdateFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Data\UpdateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

final class UpdateFlagRequest extends FlagRequest
{
    public function authorize(): bool
    {
        return $this->allows('update', $this->flag());
    }

    public function rules(): array
    {
        return UpdateFlagData::rules();
    }

    public function persist(): JsonResponse
    {
        $flag = app(UpdateFlagAction::class)->execute($this->flag(), UpdateFlagData::fromValidated($this->validated()));

        return (new FlagResource($flag))->response();
    }
}
