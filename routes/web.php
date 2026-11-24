<?php

use App\Http\Controllers\Admin\ClientController as AdminClientController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
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
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        // FR-14 reference pattern: scoped lookup, same 403 for missing and foreign records.
        Route::get('/clients/{id}', [ClientController::class, 'show'])->name('clients.show');

        // Users & Roles (wireframe 14). Super Admin only: Table 3.2 users.manage.
        Route::middleware('can:users.manage')->prefix('admin/users')->name('admin.users.')
            ->controller(UserController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/permissions', 'permissions')->name('permissions');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{user}/edit', 'edit')->name('edit');
                Route::put('/{user}', 'update')->name('update');
                Route::post('/{user}/deactivate', 'deactivate')->name('deactivate');
                Route::post('/{user}/reactivate', 'reactivate')->name('reactivate');
                Route::post('/{user}/unlock', 'unlock')->name('unlock');
                Route::post('/{user}/reset-password', 'resetPassword')->name('reset-password');
                Route::post('/{user}/reset-two-factor', 'resetTwoFactor')->name('reset-two-factor');
            });

        // Clients (wireframe 22). Super Admin only: Table 3.2 clients.manage. No delete route (NFR-7).
        Route::middleware('can:clients.manage')->prefix('admin/clients')->name('admin.clients.')
            ->controller(AdminClientController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{client}/edit', 'edit')->name('edit');
                Route::put('/{client}', 'update')->name('update');
                Route::post('/{client}/deactivate', 'deactivate')->name('deactivate');
                Route::post('/{client}/reactivate', 'reactivate')->name('reactivate');
            });

        // Activity log (wireframe 15). Read-only: GET only, no route edits or deletes a row.
        Route::get('/audit', [AuditLogController::class, 'index'])->middleware('can:audit.view')->name('audit.index');
        Route::get('/audit/export', [AuditLogController::class, 'export'])->middleware('can:audit.export')->name('audit.export');
    });
});
