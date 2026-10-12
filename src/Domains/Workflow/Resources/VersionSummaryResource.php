<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Workflow\Resources;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Request;
use Illuminate\JsonSchema\Types\Type;
use RefactorCircus\Keystone\Http\Resources\JsonResource;
use RefactorCircus\Showroom\Domains\Workflow\Models\VersionModel;

/**
 * One entry of a product's history: what happened, who did it, what changed.
 *
 * @mixin VersionModel
 */
final class VersionSummaryResource extends JsonResource
{
    /**
     * @return array<string, Type>
     */
    public static function schema(JsonSchema $schema): array
    {
        return [
            'version' => $schema->integer()->required(),
            'action' => $schema->string()->required(),
            'comment' => $schema->string()->nullable()->required(),
            'author' => $schema->object([
                'type' => $schema->string()->required(),
                'id' => $schema->union(['string', 'integer'])->required(),
            ])->nullable()->required(),
            'changes' => $schema->object()->description('field => [old, new].')->required(),
            'created_at' => $schema->string()->nullable()->required(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'version' => $this->version,
            'action' => $this->action,
            'comment' => $this->comment,
            'author' => $this->author_type === null ? null : ['type' => $this->author_type, 'id' => $this->author_id],
            'changes' => $this->changes ?? [],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
