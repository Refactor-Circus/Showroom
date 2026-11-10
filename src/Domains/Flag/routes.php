<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Showroom\Domains\Flag\Http\Controllers\FlagController;

Route::get('flags', [FlagController::class, 'index'])->name('flags.index');
Route::post('flags', [FlagController::class, 'store'])->name('flags.store');
Route::get('flags/{flag:code}', [FlagController::class, 'show'])->name('flags.show');
Route::patch('flags/{flag:code}', [FlagController::class, 'update'])->name('flags.update');
Route::delete('flags/{flag:code}', [FlagController::class, 'destroy'])->name('flags.destroy');
