<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Channel\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Channel\Actions\DeleteLocaleAction;

final class DeleteLocaleMcpRequest extends LocaleRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->locale());
    }

    protected function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $locale = app(DeleteLocaleAction::class)->execute($this->locale());

        return Response::structured(['deleted' => true, 'code' => $locale->code]);
    }
}
