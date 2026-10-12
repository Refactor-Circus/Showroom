<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Domains\Search\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Showroom\Domains\Channel\Models\ChannelModel;
use RefactorCircus\Showroom\Domains\Channel\Models\LocaleModel;

/**
 * A completeness condition: at least `min` percent on a channel — in one
 * locale, or in every locale the channel publishes in.
 */
final readonly class Complete extends Data
{
    public function __construct(
        public string $scope,
        public ?string $locale = null,
        public int $min = 100,
    ) {}

    /**
     * The condition's rules; a search takes them under `complete.`, where
     * `scope` is `required_with:complete`.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'scope' => ['required', 'string', Rule::exists(ChannelModel::class, 'code')],
            'locale' => ['sometimes', 'nullable', 'string', Rule::exists(LocaleModel::class, 'code')],
            'min' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * An empty locale means every locale.
     */
    protected static function prepare(array $validated): array
    {
        if (($validated['locale'] ?? null) === '') {
            $validated['locale'] = null;
        }

        return $validated;
    }

    /**
     * The locales the condition covers.
     *
     * @return array<int, string>
     */
    public function locales(): array
    {
        if ($this->locale !== null) {
            return [$this->locale];
        }

        $channel = ChannelModel::query()->with('locales')->where('code', $this->scope)->first();

        return $channel?->locales->pluck('code')->all() ?? [];
    }
}
