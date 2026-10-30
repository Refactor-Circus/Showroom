<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Showroom\Domains\Product\Models\ProductModel;

beforeEach(function (): void {
    ValidateCsrfToken::except(['*']);

    // Atrium denies access outside local until a gate is defined.
    app()->detectEnvironment(fn (): string => 'local');

    config()->set('showroom.media.disk', 'assets');
    Storage::fake('assets');
});

it('imports and exports from the dashboard', function (): void {
    $this->get(route('atrium.showroom.transfers.index'))->assertOk()->assertSee(__('showroom::showroom.no_runs'));

    $file = UploadedFile::fake()->createWithContent('products.jsonl', json_encode(['identifier' => 'UI-1'])."\n");

    $this->post(route('atrium.showroom.transfers.import'), ['file' => $file, 'mode' => 'upsert'])->assertSessionHasNoErrors();

    expect(ProductModel::query()->where('identifier', 'UI-1')->exists())->toBeTrue();

    $this->post(route('atrium.showroom.transfers.export'), ['format' => 'csv', 'family' => '', 'published' => '0'])->assertSessionHasNoErrors();

    $this->get(route('atrium.showroom.transfers.index'))
        ->assertOk()
        ->assertSee('data-run="showroom:import-products"', false)
        ->assertSee('data-run="showroom:export-products"', false)
        ->assertSee('1 of 1 imported, 0 failed');
});

it('explains when Impex is off', function (): void {
    config()->set('showroom.impex.enabled', false);

    $this->get(route('atrium.showroom.transfers.index'))->assertOk()->assertSee(__('showroom::showroom.impex_missing'));
});

it('names no Impex screen in its view', function (): void {
    $view = (string) file_get_contents(dirname(__DIR__, 3).'/resources/views/ui/transfers/index.blade.php');

    expect($view)->not->toContain('Impex')
        ->not->toContain('atrium.impex');
});

it('shows each run\'s flow as plain text, and renders row.actions once per run with that run', function (): void {
    foreach (['UI-1', 'UI-2'] as $identifier) {
        $file = UploadedFile::fake()->createWithContent('products.jsonl', json_encode(['identifier' => $identifier])."\n");
        $this->post(route('atrium.showroom.transfers.import'), ['file' => $file, 'mode' => 'upsert'])->assertSessionHasNoErrors();
    }

    $directory = sys_get_temp_dir().'/showroom-transfer-pages-'.getmypid();
    File::ensureDirectoryExists($directory);
    File::put($directory.'/run.blade.php', '<span>Open run {{ $record->getKey() }}</span>');
    View::addNamespace('transfer-pages', $directory);

    $runs = [];

    Atrium::screens()->section('showroom.transfers', 'row.actions', SectionDefinition::make('acme.run')
        ->view('transfer-pages::run')
        ->authorize(function (Request $request, ?Model $record) use (&$runs): bool {
            $runs[] = $record;

            return true;
        }));

    $html = (string) $this->get(route('atrium.showroom.transfers.index'))->assertOk()->getContent();

    $keys = RunModel::query()->get()->modelKeys();

    expect($runs)->toHaveCount(2)
        ->and(array_map(fn (?Model $run): string => get_debug_type($run), $runs))->toBe([RunModel::class, RunModel::class])
        ->and(array_map(fn (Model $run): mixed => $run->getKey(), $runs))->toEqualCanonicalizing($keys)
        ->and(substr_count($html, 'data-testid="section-acme.run"'))->toBe(2)
        // The flow name stands alone in its cell; the run's actions follow
        // in the row's last cell.
        ->and(preg_match_all('/<td[^>]*>showroom:import-products<\/td>/', $html))->toBe(2)
        ->and(preg_match_all('/data-testid="section-acme\.run"[^>]*>\s*<span>Open run [^<]+<\/span>\s*<\/div>\s*<\/td>\s*<\/tr>/', $html))->toBe(2);

    foreach ($keys as $key) {
        expect($html)->toContain('Open run '.$key);
    }
});
