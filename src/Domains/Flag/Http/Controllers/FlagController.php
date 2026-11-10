<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Flag\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Showroom\Domains\Flag\Http\Requests\DeleteFlagRequest;
use RefactorCircus\Showroom\Domains\Flag\Http\Requests\IndexFlagsRequest;
use RefactorCircus\Showroom\Domains\Flag\Http\Requests\ShowFlagRequest;
use RefactorCircus\Showroom\Domains\Flag\Http\Requests\StoreFlagRequest;
use RefactorCircus\Showroom\Domains\Flag\Http\Requests\UpdateFlagRequest;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

final class FlagController
{
    public function index(IndexFlagsRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function store(StoreFlagRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowFlagRequest $request, FlagModel $flag): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateFlagRequest $request, FlagModel $flag): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DeleteFlagRequest $request, FlagModel $flag): JsonResponse
    {
        return $request->persist();
    }
}
