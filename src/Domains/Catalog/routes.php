<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Showroom\Domains\Catalog\Http\Controllers\CatalogController;

// Read-only views across the catalog's domains.
Route::get('stats', [CatalogController::class, 'stats'])->name('stats.show');
