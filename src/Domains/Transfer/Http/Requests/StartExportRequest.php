<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Transfer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Impex\Domains\Run\Resources\RunResource;
use RefactorCircus\Keystone\Http\Requests\Request;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Showroom\Domains\Transfer\Data\StartExportData;

/**
 * A queued export run; its result names the file asset.
 */
final class StartExportRequest extends Request
{
    public function authorize(): bool
    {
        return parent::authorize() && $this->allows('viewAny', ProductModel::class);
    }

    public function rules(): array
    {
        return StartExportData::rules();
    }

    public function persist(): JsonResponse
    {
        $run = app(StartExportAction::class)->execute(StartExportData::fromValidated($this->validated()));

        return (new RunResource($run))->response()->setStatusCode(202);
    }
}
