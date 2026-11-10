<?php

declare(strict_types=1);

use RefactorCircus\Showroom\Domains\Product\Services\ProductChangeCapture;

/*
 * A batch write can opt out of product change capture (webhooks). The
 * opt-out is core state, held per request or job, and always restored.
 */

it('captures by default, and suppresses only while the callback runs', function (): void {
    $capture = new ProductChangeCapture;
    $inside = null;

    $result = $capture->suppress(function () use ($capture, &$inside): string {
        $inside = $capture->isSuppressed();

        return 'written';
    });

    expect($result)->toBe('written')
        ->and($inside)->toBeTrue()
        ->and($capture->isSuppressed())->toBeFalse();
});

it('restores the previous state when the callback throws', function (): void {
    $capture = new ProductChangeCapture;

    expect(fn () => $capture->suppress(fn () => throw new RuntimeException('write failed')))
        ->toThrow(RuntimeException::class, 'write failed')
        ->and($capture->isSuppressed())->toBeFalse();
});

it('restores an outer suppression when a nested one ends', function (): void {
    $capture = new ProductChangeCapture;
    $between = null;

    $capture->suppress(function () use ($capture, &$between): void {
        $capture->suppress(fn () => null);

        $between = $capture->isSuppressed();
    });

    expect($between)->toBeTrue()
        ->and($capture->isSuppressed())->toBeFalse();
});

it('is bound per request, so a suppression never leaks into the next one', function (): void {
    $first = app(ProductChangeCapture::class);
    $next = null;

    expect(app(ProductChangeCapture::class))->toBe($first);

    $first->suppress(function () use (&$next): void {
        // What Octane does between requests, and the queue between jobs.
        app()->forgetScopedInstances();

        $next = app(ProductChangeCapture::class);
    });

    expect($next)->not->toBe($first)
        ->and($next?->isSuppressed())->toBeFalse();
});
