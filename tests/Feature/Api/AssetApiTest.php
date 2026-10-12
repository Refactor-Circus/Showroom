<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Showroom\Domains\Asset\Actions\CreateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\DeleteAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Actions\UpdateAssetAction;
use RefactorCircus\Showroom\Domains\Asset\Data\CreateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Data\UpdateAssetData;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');
});

it('uploads a file to the configured disk', function (): void {
    $response = $this->post('/showroom/assets', [
        'file' => UploadedFile::fake()->image('Front View.JPG', 40, 30),
        'labels' => ['en' => 'Front view'],
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.filename', 'front-view.jpg')
        ->assertJsonPath('data.mime_type', 'image/jpeg')
        ->assertJsonPath('data.labels.en', 'Front view');

    $asset = AssetModel::query()->firstOrFail();

    expect($response->json('data.code'))->toStartWith('front-view-')
        ->and($asset->disk)->toBe('assets')
        ->and($asset->path)->toStartWith('showroom/assets/')
        ->and($asset->size)->toBeGreaterThan(0)
        ->and($asset->checksum)->toBe(hash('sha256', (string) Storage::disk('assets')->get($asset->path)));

    Storage::disk('assets')->assertExists($asset->path);
});

it('adopts a file already on the disk, as after a presigned S3 upload', function (): void {
    Storage::disk('assets')->put('incoming/manual.pdf', '%PDF-1.4 manual');

    $this->postJson('/showroom/assets', ['code' => 'tee-manual', 'path' => 'incoming/manual.pdf'])
        ->assertCreated()
        ->assertJsonPath('data.code', 'tee-manual')
        ->assertJsonPath('data.filename', 'manual.pdf')
        ->assertJsonPath('data.size', 15);

    expect(AssetModel::query()->firstOrFail()->path)->toBe('incoming/manual.pdf');

    $this->postJson('/showroom/assets', ['path' => 'incoming/missing.pdf'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['path' => 'No file exists at "incoming/missing.pdf" on the "assets" disk.']);
});

it('fetches a file from a URL', function (): void {
    Http::fake([
        'cdn.example.test/*' => Http::response('png-bytes', 200, ['Content-Type' => 'image/png; charset=binary']),
        'broken.example.test/*' => Http::response('', 404),
    ]);

    $this->postJson('/showroom/assets', ['url' => 'https://cdn.example.test/images/Tee%20Back.png'])
        ->assertCreated()
        ->assertJsonPath('data.mime_type', 'image/png')
        ->assertJsonPath('data.size', 9);

    Storage::disk('assets')->assertExists(AssetModel::query()->firstOrFail()->path);

    $this->postJson('/showroom/assets', ['url' => 'https://broken.example.test/x.png'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['url' => 'The URL answered with status 404.']);
});

it('takes exactly one source', function (): void {
    $this->postJson('/showroom/assets', [])->assertUnprocessable()->assertJsonValidationErrors(['file', 'path', 'url']);

    $this->postJson('/showroom/assets', ['path' => 'a.jpg', 'url' => 'https://example.test/a.jpg'])
        ->assertUnprocessable()->assertJsonValidationErrors('path');
});

it('enforces size and type limits on every source', function (): void {
    config()->set('showroom.media.max_kilobytes', 1);
    config()->set('showroom.media.mime_types', ['image/png']);

    $this->post('/showroom/assets', ['file' => UploadedFile::fake()->create('big.png', 5, 'image/png')], ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors('file');

    Http::fake(['*' => Http::response(str_repeat('x', 2048), 200, ['Content-Type' => 'image/png'])]);

    $this->postJson('/showroom/assets', ['url' => 'https://example.test/big.png'])
        ->assertUnprocessable()->assertJsonValidationErrors(['url' => 'The file is 2 KB; the limit is 1 KB.']);

    // What Showroom fetched and refused is not left behind.
    expect(Storage::disk('assets')->allFiles())->toBe([]);

    Storage::disk('assets')->put('incoming/doc.txt', 'text');

    $this->postJson('/showroom/assets', ['path' => 'incoming/doc.txt'])
        ->assertUnprocessable()->assertJsonValidationErrors('path');

    // An adopted file belongs to the caller, so it stays.
    Storage::disk('assets')->assertExists('incoming/doc.txt');
});

it('replaces the file but keeps the code and links', function (): void {
    Catalog::apparel();
    $this->postJson('/showroom/products', ['identifier' => 'TEE-1'])->assertCreated();

    $this->post('/showroom/assets', ['code' => 'hero', 'file' => UploadedFile::fake()->image('old.jpg')], ['Accept' => 'application/json'])->assertCreated();
    $old = AssetModel::query()->firstOrFail()->path;
    $this->postJson('/showroom/assets/hero/links', ['type' => 'product', 'target' => 'TEE-1', 'role' => 'image'])->assertOk();

    $this->post('/showroom/assets/hero', ['_method' => 'PATCH', 'file' => UploadedFile::fake()->image('new.png')], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.code', 'hero')
        ->assertJsonPath('data.filename', 'new.png');

    Storage::disk('assets')->assertMissing($old);

    $this->getJson('/showroom/products/TEE-1')->assertJsonPath('data.assets.0.code', 'hero');

    $this->patchJson('/showroom/assets/hero', ['code' => 'other'])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('links assets to products, models and owners under roles', function (): void {
    Catalog::apparel();
    $this->postJson('/showroom/owner-types', ['code' => 'brand'])->assertCreated();
    $this->postJson('/showroom/owners', ['code' => 'acme', 'type' => 'brand'])->assertCreated();
    $this->postJson('/showroom/product-models', ['code' => 'tee', 'family_variant' => 'shirts_by_size'])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'TEE-M', 'parent' => 'tee', 'values' => ['size' => Catalog::value('m')]])->assertCreated();

    foreach (['logo', 'front', 'back', 'size-chart'] as $code) {
        $this->post('/showroom/assets', ['code' => $code, 'file' => UploadedFile::fake()->image($code.'.jpg')], ['Accept' => 'application/json'])->assertCreated();
    }

    $this->postJson('/showroom/assets/logo/links', ['type' => 'owner', 'target' => 'acme', 'role' => 'logo'])->assertOk();
    $this->postJson('/showroom/assets/front/links', ['type' => 'product_model', 'target' => 'tee', 'role' => 'image', 'sort_order' => 1])->assertOk();
    $this->postJson('/showroom/assets/back/links', ['type' => 'product_model', 'target' => 'tee', 'role' => 'image', 'sort_order' => 2])->assertOk();
    $this->postJson('/showroom/assets/size-chart/links', ['type' => 'product', 'target' => 'TEE-M', 'role' => 'manual'])->assertOk()
        ->assertJsonPath('data.links', [['type' => 'product', 'target' => 'TEE-M', 'role' => 'manual', 'sort_order' => 0]]);

    $this->postJson('/showroom/assets/logo/links', ['type' => 'product', 'target' => 'NOPE'])
        ->assertUnprocessable()->assertJsonValidationErrors(['target' => 'No product "NOPE" exists.']);

    $this->getJson('/showroom/owners/acme')->assertJsonPath('data.assets.0.code', 'logo')->assertJsonPath('data.assets.0.role', 'logo');

    // A variant shows its own assets, then its model's.
    $this->getJson('/showroom/products/TEE-M')
        ->assertJsonPath('data.assets.0.code', 'size-chart')
        ->assertJsonPath('data.assets.0.inherited', false)
        ->assertJsonPath('data.assets.1.code', 'front')
        ->assertJsonPath('data.assets.2.code', 'back')
        ->assertJsonPath('data.assets.2.inherited', true);

    expect(collect($this->getJson('/showroom/assets?product_model=tee&role=image')->json('data'))->pluck('code')->sort()->values()->all())->toBe(['back', 'front'])
        ->and(collect($this->getJson('/showroom/assets?type=image/*')->json('data'))->count())->toBe(4);

    $this->deleteJson('/showroom/assets/front/links', ['type' => 'product_model', 'target' => 'tee'])->assertOk()->assertJsonPath('data.links', []);
});

it('deletes an asset with its links and its file', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'TEE-1'])->assertCreated();
    $this->post('/showroom/assets', ['code' => 'hero', 'file' => UploadedFile::fake()->image('hero.jpg')], ['Accept' => 'application/json'])->assertCreated();
    $this->postJson('/showroom/assets/hero/links', ['type' => 'product', 'target' => 'TEE-1'])->assertOk();
    $path = AssetModel::query()->firstOrFail()->path;

    $this->deleteJson('/showroom/assets/hero')->assertNoContent();

    Storage::disk('assets')->assertMissing($path);
    $this->getJson('/showroom/products/TEE-1')->assertJsonPath('data.assets', []);
});

it('keeps files when told to', function (): void {
    config()->set('showroom.media.delete_files', false);

    $this->post('/showroom/assets', ['code' => 'hero', 'file' => UploadedFile::fake()->image('hero.jpg')], ['Accept' => 'application/json'])->assertCreated();
    $path = AssetModel::query()->firstOrFail()->path;

    $this->deleteJson('/showroom/assets/hero')->assertNoContent();

    Storage::disk('assets')->assertExists($path);
});

it('removes links when the linked record goes', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'TEE-1'])->assertCreated();
    $this->post('/showroom/assets', ['code' => 'hero', 'file' => UploadedFile::fake()->image('hero.jpg')], ['Accept' => 'application/json'])->assertCreated();
    $this->postJson('/showroom/assets/hero/links', ['type' => 'product', 'target' => 'TEE-1'])->assertOk();

    $this->deleteJson('/showroom/products/TEE-1')->assertNoContent();

    $this->getJson('/showroom/assets/hero')->assertJsonPath('data.links', []);
});

it('serves temporary URLs for private disks when configured', function (): void {
    $this->post('/showroom/assets', ['code' => 'hero', 'file' => UploadedFile::fake()->image('hero.jpg')], ['Accept' => 'application/json'])->assertCreated();

    expect($this->getJson('/showroom/assets/hero')->json('data.url'))->toContain('/storage/showroom/assets/');

    config()->set('showroom.media.temporary_urls', 5);

    expect($this->getJson('/showroom/assets/hero')->json('data.url'))->toContain('expiration=');
});

/**
 * Run the work inside a transaction that then rolls back.
 */
function rollBackAfter(Closure $work): void
{
    try {
        DB::transaction(function () use ($work): void {
            $work();

            throw new RuntimeException('Rolled back.');
        });
    } catch (RuntimeException) {
        //
    }
}

it('leaves no file behind when a new asset is rolled back', function (): void {
    rollBackAfter(fn () => app(CreateAssetAction::class)->execute(new CreateAssetData(code: 'hero', file: UploadedFile::fake()->image('hero.jpg'))));

    expect(AssetModel::query()->count())->toBe(0)
        ->and(Storage::disk('assets')->allFiles())->toBe([]);
});

it('keeps the old file until a replacement commits', function (): void {
    $asset = app(CreateAssetAction::class)->execute(new CreateAssetData(code: 'hero', file: UploadedFile::fake()->image('old.jpg')));
    $old = $asset->path;

    rollBackAfter(fn () => app(UpdateAssetAction::class)->execute($asset, new UpdateAssetData(file: UploadedFile::fake()->image('new.png'))));

    expect(AssetModel::query()->sole()->path)->toBe($old)
        ->and(Storage::disk('assets')->allFiles())->toBe([$old]);
});

it('keeps the file when a delete is rolled back', function (): void {
    $asset = app(CreateAssetAction::class)->execute(new CreateAssetData(code: 'hero', file: UploadedFile::fake()->image('hero.jpg')));

    rollBackAfter(fn () => app(DeleteAssetAction::class)->execute($asset));

    expect(AssetModel::query()->count())->toBe(1);
    Storage::disk('assets')->assertExists($asset->path);
});
