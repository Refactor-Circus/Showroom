<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Impex\Domains\Run\Resources\RunResource;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartImportData;

final class StartImportMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', ProductModel::class);
    }

    protected function rules(): array
    {
        return StartImportData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $run = app(StartImportAction::class)->execute(StartImportData::fromValidated($validated));

        return (new RunResource($run))->toStructuredResponse();
    }
}
