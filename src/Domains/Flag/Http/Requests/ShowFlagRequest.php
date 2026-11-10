<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Flag\Actions\ShowFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

final class ShowFlagRequest extends FlagRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->flag());
    }

    public function persist(): JsonResponse
    {
        $flag = app(ShowFlagAction::class)->execute($this->flag());

        return (new FlagResource($flag))->response();
    }
}
