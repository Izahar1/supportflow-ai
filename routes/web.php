<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Marketing', ['product' => config('product')]))->name('home');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // User deletion is disabled while organization ownership and retention are unresolved.
    Route::delete('/profile', fn () => abort(422, 'Transfer workspace ownership before requesting account deletion.'))->name('profile.destroy');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [WorkspaceController::class, 'index'])->name('dashboard');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/super-admin', [WorkspaceController::class, 'platform']);
    Route::prefix('/app/{workspace}')->middleware('workspace')->group(function () {
        Route::get('/dashboard', [ResourceController::class, 'dashboard']);
        Route::get('/team', [WorkspaceController::class, 'team']);
        Route::post('/team', [WorkspaceController::class, 'addMember']);
        Route::get('/billing', [BillingController::class, 'index']);
        Route::post('/billing/checkout', [BillingController::class, 'checkout']);
        Route::post('/billing/portal', [BillingController::class, 'portal']);
        Route::get('/{module}/{id}', [ResourceController::class, 'show'])->whereNumber('id');
        Route::get('/{module}', [ResourceController::class, 'index']);
        Route::post('/{module}', [ResourceController::class, 'store'])->middleware('throttle:60,1');
        Route::post('/{module}/{id}/{action}', [ResourceController::class, 'action'])->whereNumber('id')->middleware('throttle:60,1');
    });
});
require __DIR__.'/auth.php';
