<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use RefactorCircus\Keystone\Models\Concerns\DispatchesModelEvents;
use RefactorCircus\Showroom\Support\Tables;

/**
 * The shape a Scout engine indexes a product in.
 *
 * Not the Product model: Scout's trait would tie every Showroom install to
 * Scout. This stand-in reads the same table and carries the prepared
 * document, and is only loaded when the `scout` search engine is chosen.
 * Like every model it fires a `SearchableProduct{Hook}Event` per hook.
 *
 * @property string $id
 */
final class SearchableProductModel extends Model
{
    use DispatchesModelEvents;
    use Searchable;

    public $incrementing = false;

    public function getTable(): string
    {
        return Tables::name('products');
    }

    protected $keyType = 'string';

    /**
     * @var array<string, mixed>
     */
    private array $document = [];

    /**
     * @param  array<string, mixed>  $document
     */
    public static function fromDocument(array $document): self
    {
        $product = new self;
        $product->setAttribute('id', $document['id'] ?? null);
        $product->document = $document;

        return $product;
    }

    public function searchableAs(): string
    {
        return (string) config('showroom.search.scout.index', $this->getTable());
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return $this->document;
    }
}
