<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Channel\Actions\ListLocalesAction;
use RefactorCircus\Showroom\Domains\Channel\Data\ListLocalesData;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;
use RefactorCircus\Showroom\Domains\Channel\Resources\LocaleResource;

final class IndexLocalesRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', LocaleModel::class);
    }

    public function rules(): array
    {
        return ListLocalesData::rules();
    }

    public function persist(): JsonResponse
    {
        $locales = app(ListLocalesAction::class)->execute(ListLocalesData::fromValidated($this->validated()));

        return LocaleResource::collection($locales)->response();
    }
}
