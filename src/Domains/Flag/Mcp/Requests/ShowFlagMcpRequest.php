<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Flag\Actions\ShowFlagAction;
use RefactorCircus\Showroom\Domains\Flag\Resources\FlagResource;

final class ShowFlagMcpRequest extends FlagRequest
{
    protected function authorize(): bool
    {
        return $this->allows('view', $this->flag());
    }

    protected function rules(): array
    {
        return [
            'flag' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $flag = app(ShowFlagAction::class)->execute($this->flag());

        return (new FlagResource($flag))->toStructuredResponse();
    }
}
