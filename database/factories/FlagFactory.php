<?php

declare(strict_types=1);

namespace RefactorCircus\Showroom\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RefactorCircus\Showroom\Domains\Flag\Enums\FlagSubject;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;

/**
 * A flag that applies to products and owners alike.
 *
 * @extends Factory<FlagModel>
 */
final class FlagFactory extends Factory
{
    protected $model = FlagModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'flag_'.fake()->unique()->lexify('??????'),
            'labels' => [],
            'applies_to' => FlagSubject::values(),
            'sort_order' => 0,
        ];
    }
}
