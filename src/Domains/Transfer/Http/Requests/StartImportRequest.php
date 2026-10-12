<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Impex\Domains\Run\Resources\RunResource;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartImportData;

/**
 * A queued import run; follow it through Impex.
 */
final class StartImportRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('create', ProductModel::class);
    }

    public function rules(): array
    {
        return StartImportData::rules();
    }

    public function persist(): JsonResponse
    {
        $run = app(StartImportAction::class)->execute(StartImportData::fromValidated($this->validated()));

        return (new RunResource($run))->response()->setStatusCode(202);
    }
}
