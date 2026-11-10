<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Showroom\Domains\Flag\Actions\DeleteFlagAction;

final class DeleteFlagMcpRequest extends FlagRequest
{
    protected function authorize(): bool
    {
        return $this->allows('delete', $this->flag());
    }

    protected function rules(): array
    {
        return [
            'flag' => ['required', 'string', 'max:100'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $flag = app(DeleteFlagAction::class)->execute($this->flag());

        return Response::structured(['deleted' => true, 'code' => $flag->code]);
    }
}
