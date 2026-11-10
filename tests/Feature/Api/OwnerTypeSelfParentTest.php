<?php

declare(strict_types=1);

it('creates an owner type that lists itself as a parent', function (): void {
    $this->postJson('/showroom/owner-types', ['code' => 'vendor', 'parent_types' => ['vendor']])
        ->assertCreated()
        ->assertJsonPath('data.parent_types', ['vendor']);

    $this->getJson('/showroom/owner-types/vendor')->assertOk()->assertJsonPath('data.parent_types', ['vendor']);
});

it('places an owner under another owner of the same self-parenting type', function (): void {
    $this->postJson('/showroom/owner-types', ['code' => 'vendor', 'parent_types' => ['vendor']])->assertCreated();

    $this->postJson('/showroom/owners', ['code' => 'acme', 'type' => 'vendor'])->assertCreated();
    $this->postJson('/showroom/owners', ['code' => 'acme-eu', 'type' => 'vendor', 'parent' => 'acme'])
        ->assertCreated()
        ->assertJsonPath('data.parent', 'acme');
});

it('still refuses a parent type that does not exist', function (): void {
    $this->postJson('/showroom/owner-types', ['code' => 'vendor', 'parent_types' => ['vendor', 'brand']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_types.1'])
        ->assertJsonMissingValidationErrors(['parent_types.0']);

    $this->getJson('/showroom/owner-types/vendor')->assertNotFound();
});
