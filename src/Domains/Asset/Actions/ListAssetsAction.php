<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Asset\Actions;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Showroom\Domains\Asset\Data\ListAssetsData;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetsListedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Events\AssetsListingActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Support\Tables;

final class ListAssetsAction
{
    /**
     * @return CursorPaginator<int, AssetModel>
     */
    public function execute(ListAssetsData $filters = new ListAssetsData): CursorPaginator
    {
        AssetsListingActionEvent::dispatch($filters);

        $result = $this->perform($filters);

        AssetsListedActionEvent::dispatch($result);

        return $result;
    }

    /**
     * @return CursorPaginator<int, AssetModel>
     */
    private function perform(ListAssetsData $filters): CursorPaginator
    {
        $query = AssetModel::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($filters->search !== null && $filters->search !== '') {
            $search = $filters->search;

            $query->where(fn (Builder $builder): Builder => $builder
                ->search($search)
                ->orWhere('filename', 'like', '%'.$search.'%'));
        }

        if ($filters->type !== null && $filters->type !== '') {
            $type = $filters->type;

            str_ends_with($type, '/*') || str_ends_with($type, '/')
                ? $query->where('mime_type', 'like', rtrim($type, '/*').'/%')
                : $query->where('mime_type', $type);
        }

        $role = $filters->role !== null && $filters->role !== '' ? $filters->role : null;

        foreach ([[$filters->product, 'products', 'identifier'], [$filters->productModel, 'productModels', 'code'], [$filters->owner, 'owners', 'code']] as [$target, $relation, $key]) {
            if ($target !== null && $target !== '') {
                $query->whereHas($relation, fn (Builder $linked): Builder => $linked
                    ->where($key, $target)
                    ->when($role !== null, fn (Builder $linked): Builder => $linked->where(Tables::name('asset_links').'.role', $role)));
            }
        }

        return $query->cursorPaginate(
            perPage: $filters->perPage ?? ListAssetsData::pagination()->default,
            cursor: $filters->cursor,
        );
    }
}
