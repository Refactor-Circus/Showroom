<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Family\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Attribute\Models\AttributeModel;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;

/**
 * One member of a family's attribute list: the attribute, whether it is
 * required (on every channel, or only on `required_channels`), and its
 * position, which defaults to its place in the list.
 */
final readonly class FamilyAttributeData extends Data
{
    /**
     * @param  list<string>|null  $requiredChannels
     */
    public function __construct(
        public string $attribute,
        public bool $isRequired = false,
        public ?int $sortOrder = null,
        public ?array $requiredChannels = null,
    ) {}

    /**
     * A member's rules; a family's list takes them under `attributes.*`.
     * `distinct` keeps an attribute from being listed twice.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'attribute' => ['required', 'string', 'distinct', Rule::exists(AttributeModel::class, 'code')],
            'is_required' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            // Narrow a requirement to some channels; without it, a required
            // attribute is required on every channel.
            'required_channels' => ['sometimes', 'nullable', 'array', 'list'],
            'required_channels.*' => ['string', 'distinct', Rule::exists(ChannelModel::class, 'code')],
        ];
    }
}
