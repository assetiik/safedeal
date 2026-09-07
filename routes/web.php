<?php

use App\Http\Controllers\Admin\Web\AuditController;
use App\Http\Controllers\Admin\Web\AuthController;
use App\Http\Controllers\Admin\Web\DashboardController;
use App\Http\Controllers\Admin\Web\DealController;
use App\Http\Controllers\Admin\Web\DisputeController;
use App\Http\Controllers\Admin\Web\DocumentController;
use App\Http\Controllers\Admin\Web\PaymentController;
use App\Http\Controllers\Admin\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware(['auth', 'admin.web'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('deals', [DealController::class, 'index'])->name('deals.index');
        Route::get('deals/{deal}', [DealController::class, 'show'])->name('deals.show');
        Route::post('deals/{deal}/force-status', [DealController::class, 'forceStatus'])->name('deals.force-status');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('users/{user}/block', [UserController::class, 'block'])->name('users.block');
        Route::post('users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');

        Route::get('disputes', [DisputeController::class, 'index'])->name('disputes.index');
        Route::get('disputes/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
        Route::post('disputes/{dispute}/take', [DisputeController::class, 'take'])->name('disputes.take');
        Route::post('disputes/{dispute}/resolve', [DisputeController::class, 'resolve'])->name('disputes.resolve');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    });
});
