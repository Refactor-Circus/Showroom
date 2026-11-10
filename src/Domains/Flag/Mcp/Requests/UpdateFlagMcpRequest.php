<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Flag\Actions\UpdateFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Data\UpdateFlagData;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

final class UpdateFlagMcpRequest extends FlagRequest
{
    protected function authorize(): bool
    {
        return $this->allows('update', $this->flag());
    }

    protected function rules(): array
    {
        return [
            'flag' => ['required', 'string', 'max:100'],
        ] + UpdateFlagData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $flag = app(UpdateFlagAction::class)->execute($this->flag(), UpdateFlagData::fromValidated($validated));

        return (new FlagResource($flag))->toStructuredResponse();
    }
}
