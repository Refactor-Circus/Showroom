<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Channel\Actions\ShowLocaleAction;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

final class ShowLocaleRequest extends LocaleRequest
{
    public function authorize(): bool
    {
        return $this->allows('view', $this->locale());
    }

    public function persist(): JsonResponse
    {
        $locale = app(ShowLocaleAction::class)->execute($this->locale());

        return (new LocaleResource($locale))->response();
    }
}
