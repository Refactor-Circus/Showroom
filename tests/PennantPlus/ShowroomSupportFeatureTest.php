<?php

declare(strict_types=1);

use Laravel\Pennant\Feature;
use RefactorCircus\Showroom\Atrium\Features\ShowroomSupportFeature;

// Pennant's array store keeps values in its cache: Feature::flushCache()
// would wipe them, so these tests never call it.

// The Atrium plugin that read this feature is gone; the feature itself goes
// with PennantPlus in the next step of the v2 spec.
it('is on until its global value is turned off', function (): void {
    expect(Feature::for(null)->active(ShowroomSupportFeature::class))->toBeTrue();

    Feature::for(null)->deactivate(ShowroomSupportFeature::class);

    expect(Feature::for(null)->active(ShowroomSupportFeature::class))->toBeFalse();
});
