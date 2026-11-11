<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use RefactorCircus\Showroom\Domains\Flag\Models\FlagModel;
use RefactorCircus\Showroom\Domains\Flag\Services\Flags;
use RefactorCircus\Showroom\Domains\Owner\Actions\CreateOwnerAction;
use RefactorCircus\Showroom\Domains\Owner\Data\CreateOwnerData;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerModel;
use RefactorCircus\Showroom\Domains\Owner\Models\OwnerTypeModel;

/*
 * Flags::trusted() runs a trusted system sync — a queued import, an ERP
 * sync — with every flag assignment allowed, since no signed-in user is
 * there for the `assign` ability to allow. It ends with its callback,
 * returned or thrown, and nests.
 */

beforeEach(function (): void {
    OwnerTypeModel::factory()->create(['code' => 'vendor']);
    FlagModel::factory()->create(['code' => 'restricted']);

    // Authorization on, and no one signed in: a queued job.
    config()->set('showroom.authorization', true);
});

/**
 * Whether Flags allows changing an owner's flags right now.
 */
function trustedMayAssign(): bool
{
    try {
        app(Flags::class)->authorizeAssign(new OwnerModel(['code' => 'acme']));
    } catch (AuthorizationException) {
        return false;
    }

    return true;
}

it('refuses flag assignment without a user outside trusted mode, and allows it inside', function (): void {
    expect(trustedMayAssign())->toBeFalse()
        ->and(Flags::trusted(fn (): bool => trustedMayAssign()))->toBeTrue()
        ->and(trustedMayAssign())->toBeFalse();
});

it('returns what its callback returns', function (): void {
    expect(Flags::trusted(fn (): string => 'synced'))->toBe('synced');
});

it('ends trusted mode when its callback throws', function (): void {
    expect(fn () => Flags::trusted(function (): never {
        throw new RuntimeException('The sync broke.');
    }))->toThrow(RuntimeException::class, 'The sync broke.')
        ->and(trustedMayAssign())->toBeFalse();
});

it('nests: trusted mode lasts until the outermost call ends', function (): void {
    $seen = Flags::trusted(function (): array {
        $inner = Flags::trusted(fn (): bool => trustedMayAssign());

        return [$inner, trustedMayAssign()];
    });

    expect($seen)->toBe([true, true])
        ->and(trustedMayAssign())->toBeFalse();

    // An inner call that throws leaves the outer one trusted.
    $afterThrow = Flags::trusted(function (): bool {
        try {
            Flags::trusted(function (): never {
                throw new RuntimeException('Inner sync broke.');
            });
        } catch (RuntimeException) {
            //
        }

        return trustedMayAssign();
    });

    expect($afterThrow)->toBeTrue()
        ->and(trustedMayAssign())->toBeFalse();
});

it('lets the owner actions write flags inside trusted mode without a user', function (): void {
    $create = fn (string $code): OwnerModel => app(CreateOwnerAction::class)->execute(CreateOwnerData::from(['code' => $code, 'type' => 'vendor', 'flags' => ['restricted']]));

    expect(fn () => $create('acme'))->toThrow(AuthorizationException::class);

    $owner = Flags::trusted(fn (): OwnerModel => $create('acme'));

    expect($owner->flags->pluck('code')->all())->toBe(['restricted'])
        ->and(fn () => $create('globex'))->toThrow(AuthorizationException::class);
});
