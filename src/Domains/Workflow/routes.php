<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Showroom\Domains\Workflow\Http\Controllers\ProductWorkflowController;

// `{product}` binds by the configured lookup key, or a call's `?by=`.
Route::post('products/{product}/transitions', [ProductWorkflowController::class, 'transition'])->name('products.transitions.store');
Route::get('products/{product}/versions', [ProductWorkflowController::class, 'versions'])->name('products.versions.index');
// A number, `latest`, or `published` for the version storefronts read.
Route::get('products/{product}/versions/{version}', [ProductWorkflowController::class, 'version'])->name('products.versions.show');
Route::post('products/{product}/revert', [ProductWorkflowController::class, 'revert'])->name('products.revert');
