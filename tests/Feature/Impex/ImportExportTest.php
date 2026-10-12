<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RefactorCircus\Impex\Domains\Flow\Services\FlowRegistry;
use RefactorCircus\Impex\Domains\Message\Models\MessageModel;
use RefactorCircus\Impex\Domains\Run\Enums\RunStatus;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Impex\Facades\Impex;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;
use RefactorCircus\Showroom\Domains\Asset\Models\AssetModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;
use RefactorCircus\Showroom\Domains\Transfer\Events\FeedDeliveredActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\FeedDeliveringActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductExportJoinedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductExportJoiningActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductPagesExportedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductPagesExportingActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductRowImportedActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Events\ProductRowImportingActionEvent;
use RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools\StartExportTool;
use RefactorCircus\Showroom\Domains\Transfer\Mcp\Tools\StartImportTool;
use RefactorCircus\Showroom\Tests\Fixtures\Catalog;

beforeEach(function (): void {
    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');

    Catalog::apparel();
    $this->postJson('/showroom/categories', ['code' => 'master'])->assertCreated();
});

function fileAsset(string $code, string $filename, string $contents): void
{
    Storage::disk('assets')->put('incoming/'.$filename, $contents);

    test()->postJson('/showroom/assets', ['code' => $code, 'path' => 'incoming/'.$filename])->assertCreated();
}

function finishedRun(string $id): RunModel
{
    $run = RunModel::query()->findOrFail($id);

    expect($run->status)->toBe(RunStatus::Completed, (string) json_encode($run->error));

    return $run;
}

it('registers its flows with Impex, one per feed', function (): void {
    config()->set('showroom.impex.feeds', ['web' => ['channel' => 'ecommerce']]);
    app()->forgetInstance(FlowRegistry::class);

    $flows = app(FlowRegistry::class);

    expect($flows->has('showroom:import-products'))->toBeTrue()
        ->and($flows->has('showroom:upsert-products'))->toBeTrue()
        ->and($flows->has('showroom:export-products'))->toBeTrue()
        ->and($flows->has('showroom:feed:web'))->toBeTrue();
});

it('imports a CSV file with Akeneo columns', function (): void {
    fileAsset('shirts-csv', 'shirts.csv', implode("\n", [
        "\u{FEFF}identifier,family,enabled,categories,name,description-en,description-fr,color,tags,weight,weight-unit,price-USD,price-EUR,organic,pack_size",
        'TEE-1,shirts,1,master,Classic tee,Soft,Doux,red,"summer,new",180,gram,19.99,17.50,1,3',
        'TEE-2,shirts,0,,Second tee,,,blue,,,,,,0,',
        'TEE-3,shirts,1,,Bad tee,,,purple,,,,,,,',
    ]));

    $id = $this->postJson('/showroom/imports', ['asset' => 'shirts-csv'])->assertStatus(202)->json('data.id');

    expect(Impex::result(finishedRun($id)))->toMatchArray(['total' => 3, 'succeeded' => 2, 'failed' => 1]);

    $this->getJson('/showroom/products/TEE-1')
        ->assertJsonPath('data.family', 'shirts')
        ->assertJsonPath('data.categories', ['master'])
        ->assertJsonPath('data.values.name.0.data', 'Classic tee')
        ->assertJsonPath('data.values.tags.0.data', ['summer', 'new'])
        ->assertJsonPath('data.values.weight.0.data', ['amount' => '180', 'unit' => 'gram'])
        ->assertJsonPath('data.values.price.0.data', [['amount' => '19.99', 'currency' => 'USD'], ['amount' => '17.50', 'currency' => 'EUR']])
        ->assertJsonPath('data.values.organic.0.data', true)
        ->assertJsonPath('data.values.pack_size.0.data', 3);

    $this->getJson('/showroom/products/TEE-2')->assertJsonPath('data.enabled', false)->assertJsonPath('data.values.organic.0.data', false);

    // The bad row's reason is kept with the batch item.
    $failed = collect(Impex::batchItems(Impex::result(finishedRun($id))['batch_id']))->firstWhere('status.value', 'failed');

    expect($failed?->error['message'] ?? '')->toContain('values.color.0.data');
});

it('imports JSONL in each mode', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'OLD', 'values' => ['name' => Catalog::value('Old name')]])->assertCreated();

    fileAsset('products-jsonl', 'products.jsonl', implode("\n", [
        json_encode(['identifier' => 'OLD', 'values' => ['name' => Catalog::value('New name')]]),
        json_encode(['identifier' => 'NEW', 'values' => ['name' => Catalog::value('Brand new')]]),
        '',
        'not json',
    ]));

    $create = $this->postJson('/showroom/imports', ['asset' => 'products-jsonl', 'mode' => 'create'])->json('data.id');

    expect(Impex::result(finishedRun($create)))->toMatchArray(['succeeded' => 2, 'failed' => 1]);
    expect(ProductModel::query()->where('identifier', 'OLD')->firstOrFail()->value('name'))->toBe('Old name');

    $update = $this->postJson('/showroom/imports', ['asset' => 'products-jsonl', 'mode' => 'update'])->json('data.id');
    finishedRun($update);

    expect(ProductModel::query()->where('identifier', 'OLD')->firstOrFail()->value('name'))->toBe('New name');
});

it('fetches the file to import from a URL', function (): void {
    // A fresh response per request: a streamed body is read once.
    Http::fake(['*' => fn () => Http::response(json_encode(['identifier' => 'URL-1'])."\n", 200, ['Content-Type' => 'application/x-ndjson'])]);

    $id = $this->postJson('/showroom/imports', ['url' => 'https://erp.example.test/export.jsonl'])->assertStatus(202)->json('data.id');

    finishedRun($id);
    expect(ProductModel::query()->where('identifier', 'URL-1')->exists())->toBeTrue();

    $this->postJson('/showroom/imports', ['url' => 'https://erp.example.test/export.xml'])
        ->assertUnprocessable()->assertJsonValidationErrors('format');
});

it('upserts records pushed by a connector', function (): void {
    $run = Impex::run('showroom:upsert-products', [['products' => [
        ['identifier' => 'ERP-1', 'values' => ['name' => Catalog::value('From the ERP')]],
        ['identifier' => 'ERP-2', 'family' => 'nope'],
    ]]]);

    expect(Impex::result(finishedRun($run->id)))->toMatchArray(['total' => 2, 'succeeded' => 1, 'failed' => 1]);
    expect(ProductModel::query()->where('identifier', 'ERP-1')->exists())->toBeTrue();
});

it('exports matching products to a JSONL or CSV asset', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'A', 'family' => 'shirts', 'categories' => ['master'], 'values' => [
        'name' => Catalog::value('Alpha'),
        'description' => [['locale' => 'en', 'scope' => null, 'data' => 'Soft'], ['locale' => 'fr', 'scope' => null, 'data' => 'Doux']],
        'weight' => Catalog::value(['amount' => 180, 'unit' => 'gram']),
    ]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'B', 'values' => ['name' => Catalog::value('Beta')]])->assertCreated();

    $jsonl = $this->postJson('/showroom/exports', ['family' => 'shirts', 'locales' => ['en'], 'code' => 'shirts-export'])->assertStatus(202)->json('data.id');

    expect(Impex::result(finishedRun($jsonl)))->toBe(['asset' => 'shirts-export', 'count' => 1, 'format' => 'jsonl']);

    $asset = AssetModel::query()->where('code', 'shirts-export')->firstOrFail();
    $lines = array_values(array_filter(explode("\n", (string) Storage::disk('assets')->get($asset->path))));
    $record = json_decode($lines[0], true);

    expect($lines)->toHaveCount(1)
        ->and($record['identifier'])->toBe('A')
        ->and($record['values']['description'])->toBe([['locale' => 'en', 'scope' => null, 'data' => 'Soft']]);

    $csv = $this->postJson('/showroom/exports', ['format' => 'csv'])->json('data.id');
    $result = Impex::result(finishedRun($csv));

    $file = (string) Storage::disk('assets')->get(AssetModel::query()->where('code', $result['asset'])->firstOrFail()->path);
    $rows = array_map(static fn (string $line): array => str_getcsv($line, escape: ''), array_values(array_filter(explode("\n", $file))));

    expect($rows[0])->toContain('identifier', 'description-en', 'description-fr', 'weight', 'weight-unit')
        ->and($rows)->toHaveCount(3)
        ->and(array_combine($rows[0], $rows[1])['weight-unit'])->toBe('gram');

    // What was exported imports back.
    ProductModel::query()->delete();
    $back = $this->postJson('/showroom/imports', ['asset' => $result['asset']])->json('data.id');

    expect(Impex::result(finishedRun($back)))->toMatchArray(['succeeded' => 2, 'failed' => 0]);
});

it('exports the products value filters match, replaying them from the run', function (): void {
    $this->postJson('/showroom/products', ['identifier' => 'A', 'values' => ['name' => Catalog::value('Alpha')]])->assertCreated();
    $this->postJson('/showroom/products', ['identifier' => 'B', 'values' => ['name' => Catalog::value('Beta')]])->assertCreated();

    $id = $this->postJson('/showroom/exports', [
        'filters' => [['attribute' => 'name', 'operator' => 'in', 'value' => ['Alpha']]],
        'code' => 'alpha',
    ])->assertStatus(202)->json('data.id');

    expect(Impex::result(finishedRun($id))['count'])->toBe(1);

    $record = json_decode(trim((string) Storage::disk('assets')->get(AssetModel::query()->where('code', 'alpha')->firstOrFail()->path)), true);

    expect($record['identifier'])->toBe('A');
});

it('exports live versions only when asked', function (): void {
    config()->set('showroom.workflow.require_approval', false);

    $this->postJson('/showroom/products', ['identifier' => 'LIVE', 'values' => ['name' => Catalog::value('Live name')]])->assertCreated();
    $this->postJson('/showroom/products/LIVE/transitions', ['transition' => 'publish'])->assertOk();
    $this->patchJson('/showroom/products/LIVE', ['values' => ['name' => Catalog::value('Draft name')]])->assertOk();
    $this->postJson('/showroom/products', ['identifier' => 'NEVER'])->assertCreated();

    $id = $this->postJson('/showroom/exports', ['published' => true, 'code' => 'live'])->json('data.id');

    expect(Impex::result(finishedRun($id))['count'])->toBe(1);

    $record = json_decode(trim((string) Storage::disk('assets')->get(AssetModel::query()->where('code', 'live')->firstOrFail()->path)), true);

    expect($record['values']['name'][0]['data'])->toBe('Live name');
});

it('builds and delivers a channel feed', function (): void {
    config()->set('showroom.workflow.require_approval', false);
    config()->set('showroom.impex.feeds', ['web' => ['channel' => 'print', 'format' => 'jsonl', 'url' => 'https://feeds.example.test/in', 'ledger_channel' => 'web-feed']]);
    app()->forgetInstance(FlowRegistry::class);

    Http::fake(['feeds.example.test/*' => Http::response(['ok' => true], 200)]);

    $this->postJson('/showroom/products', ['identifier' => 'LIVE', 'values' => ['description' => [
        ['locale' => 'en', 'scope' => null, 'data' => 'English'],
        ['locale' => 'fr', 'scope' => null, 'data' => 'French'],
    ]]])->assertCreated();
    $this->postJson('/showroom/products/LIVE/transitions', ['transition' => 'publish'])->assertOk();

    $run = Impex::run('showroom:feed:web');
    $result = Impex::result(finishedRun($run->id));

    expect($result['count'])->toBe(1)
        ->and($result['delivery'])->toBe(['status' => 200]);

    $record = json_decode(trim((string) Storage::disk('assets')->get(AssetModel::query()->where('code', $result['asset'])->firstOrFail()->path)), true);

    // The print channel publishes in English only.
    expect($record['values']['description'])->toBe([['locale' => 'en', 'scope' => null, 'data' => 'English']]);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://feeds.example.test/in');
    expect(MessageModel::query()->where('channel', 'web-feed')->where('run_id', $run->id)->exists())->toBeTrue();
});

it('fires a starting and a finished event around every flow step', function (): void {
    config()->set('showroom.workflow.require_approval', false);
    config()->set('showroom.impex.feeds', ['web' => ['channel' => 'print', 'format' => 'jsonl', 'url' => 'https://feeds.example.test/in']]);
    app()->forgetInstance(FlowRegistry::class);
    Http::fake(['feeds.example.test/*' => Http::response(['ok' => true], 200)]);

    /** @var ArrayObject<int, class-string> $seen */
    $seen = new ArrayObject;
    Event::listen([ActionStartingEvent::class, ActionFinishedEvent::class], function (object $event) use ($seen): void {
        $seen->append($event::class);
    });

    fileAsset('one', 'one.jsonl', (string) json_encode(['identifier' => 'LIVE']));
    finishedRun($this->postJson('/showroom/imports', ['asset' => 'one'])->json('data.id'));
    $this->postJson('/showroom/products/LIVE/transitions', ['transition' => 'publish'])->assertOk();
    finishedRun(Impex::run('showroom:feed:web')->id);

    expect($seen->getArrayCopy())->toContain(
        ProductRowImportingActionEvent::class, ProductRowImportedActionEvent::class,
        ProductPagesExportingActionEvent::class, ProductPagesExportedActionEvent::class,
        ProductExportJoiningActionEvent::class, ProductExportJoinedActionEvent::class,
        FeedDeliveringActionEvent::class, FeedDeliveredActionEvent::class,
    );
});

it('keeps per-row and per-page steps out of the audit log', function (): void {
    expect(array_filter(
        [ProductRowImportingActionEvent::class, ProductRowImportedActionEvent::class, ProductPagesExportingActionEvent::class, ProductPagesExportedActionEvent::class],
        fn (string $event): bool => ! is_subclass_of($event, UnauditedActionEvent::class),
    ))->toBeEmpty()
        ->and(is_subclass_of(ProductExportJoinedActionEvent::class, UnauditedActionEvent::class))->toBeFalse();
});

it('delivers a feed through a named Impex channel, the channel\'s way', function (): void {
    config()->set('showroom.workflow.require_approval', false);
    config()->set('impex.channels.partner-drop', [
        'direction' => 'outbound',
        'transport' => 'file',
        'options' => ['disk' => 'drops', 'path' => 'incoming/{id}.jsonl'],
    ]);
    config()->set('showroom.impex.feeds', ['partner' => ['channel' => 'print', 'deliver_through' => 'partner-drop']]);
    app()->forgetInstance(FlowRegistry::class);
    Storage::fake('drops');

    $this->postJson('/showroom/products', ['identifier' => 'LIVE'])->assertCreated();
    $this->postJson('/showroom/products/LIVE/transitions', ['transition' => 'publish'])->assertOk();

    $run = Impex::run('showroom:feed:partner');
    $result = Impex::result(finishedRun($run->id));

    Storage::disk('drops')->assertExists('incoming/'.$result['asset'].'.jsonl');

    expect(MessageModel::query()->where('channel', 'partner-drop')->where('run_id', $run->id)->sole()->transport)->toBe('file');
});

it('starts imports and exports over MCP', function (): void {
    fileAsset('products-jsonl', 'products.jsonl', json_encode(['identifier' => 'MCP-1'])."\n");

    mcpTool(StartImportTool::class, ['asset' => 'products-jsonl'])->assertOk()->assertSee('showroom:import-products');
    mcpTool(StartExportTool::class, ['format' => 'csv'])->assertOk()->assertSee('showroom:export-products');

    expect(ProductModel::query()->where('identifier', 'MCP-1')->exists())->toBeTrue();
});

it('says so when Impex is switched off', function (): void {
    config()->set('showroom.impex.enabled', false);

    $this->postJson('/showroom/exports', [])
        ->assertStatus(501)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'composer require refactor-circus/impex'));
});

it('fails the run when more rows fail than tolerated', function (): void {
    config()->set('showroom.impex.allow_failures', 0.0);

    fileAsset('bad-jsonl', 'bad.jsonl', "not json\n");

    $id = $this->postJson('/showroom/imports', ['asset' => 'bad-jsonl'])->json('data.id');

    expect(RunModel::query()->findOrFail($id)->status)->toBe(RunStatus::Failed);
});
