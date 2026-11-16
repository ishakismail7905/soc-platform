<?php

use App\Http\Controllers\SecurityController;
use Illuminate\Support\Facades\Route;

// No public home page: guests go to sign-in, signed-in users to their dashboard.
Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

// Every signed-in page passes these checks, in this order:
//   auth           -> signed in
//   account.usable -> not locked or deactivated (Task 5)
//   security.setup -> temporary password changed, 2FA set up where required (Task 6)
Route::middleware(['auth', 'account.usable'])->group(function () {
    // The one page an unfinished account is allowed to open.
    Route::get('/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::middleware('security.setup')->group(function () {
        // Placeholder until Task 7 builds the real dashboard.
        Route::get('/dashboard', fn () => 'Dashboard placeholder (Task 7)')->name('dashboard');
    });
});