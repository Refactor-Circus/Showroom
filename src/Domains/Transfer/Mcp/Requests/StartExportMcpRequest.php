<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Impex\Domains\Run\Resources\RunResource;
use RefactorCircus\Keystone\Mcp\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartExportData;

final class StartExportMcpRequest extends Request
{
    protected function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModel::class);
    }

    protected function rules(): array
    {
        return StartExportData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $run = app(StartExportAction::class)->execute(StartExportData::fromValidated($validated));

        return (new RunResource($run))->toStructuredResponse();
    }
}
