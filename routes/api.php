<?php

use App\Http\Controllers\Api\V1\Admin\AdminDealController;
use App\Http\Controllers\Api\V1\Admin\AdminDisputeController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DealController;
use App\Http\Controllers\Api\V1\DisputeController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:auth')->group(function (): void {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
    });

    Route::post('payments/webhooks/{provider}', [PaymentController::class, 'webhook']);

    Route::get('documents/{document}/file', [DocumentController::class, 'file'])
        ->middleware('signed')
        ->name('documents.file');

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('me', [MeController::class, 'show']);
        Route::patch('me/profile', [MeController::class, 'updateProfile']);
        Route::post('me/change-password', [MeController::class, 'changePassword']);

        Route::middleware('role:customer,contractor')->group(function (): void {
            Route::get('dashboard', [DashboardController::class, 'show']);

            Route::get('deals', [DealController::class, 'index']);
            Route::post('deals', [DealController::class, 'store']);
            Route::get('orders/open', [DealController::class, 'openOrders']);
            Route::get('deals/{deal}', [DealController::class, 'show']);
            Route::get('deals/{deal}/history', [DealController::class, 'history']);
            Route::post('deals/{deal}/actions/{action}', [DealController::class, 'action']);
            Route::get('deals/{deal}/contract', [DealController::class, 'contract']);
            Route::post('deals/{deal}/contract/confirm', [DealController::class, 'confirmContract']);

            Route::get('payments', [PaymentController::class, 'index']);
            Route::get('deals/{deal}/payment', [PaymentController::class, 'show']);
            Route::post('deals/{deal}/payment/reserve', [PaymentController::class, 'reserve'])
                ->middleware('throttle:payments');

            Route::get('deals/{deal}/documents', [DocumentController::class, 'forDeal']);
            Route::post('deals/{deal}/documents', [DocumentController::class, 'store']);
            Route::post('deals/{deal}/disputes', [DisputeController::class, 'store']);

            Route::get('disputes', [DisputeController::class, 'index']);
            Route::get('disputes/{dispute}', [DisputeController::class, 'show']);

            Route::get('documents', [DocumentController::class, 'index']);
            Route::get('documents/{document}', [DocumentController::class, 'show']);
            Route::get('documents/{document}/download', [DocumentController::class, 'download']);

            Route::get('notifications', [NotificationController::class, 'index']);
            Route::post('notifications/{notification}/read', [NotificationController::class, 'read']);
            Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
        });

        Route::prefix('admin')->middleware('role:admin')->group(function (): void {
            Route::get('dashboard', [AdminDealController::class, 'dashboard']);
            Route::get('users', [AdminUserController::class, 'index']);
            Route::post('users/{user}/block', [AdminUserController::class, 'block']);
            Route::post('users/{user}/unblock', [AdminUserController::class, 'unblock']);
            Route::get('deals', [AdminDealController::class, 'index']);
            Route::get('deals/{deal}', [AdminDealController::class, 'show']);
            Route::post('deals/{deal}/force-status', [AdminDealController::class, 'forceStatus']);
            Route::get('payments', [AdminDealController::class, 'payments']);
            Route::get('documents', [AdminDealController::class, 'documents']);
            Route::get('audit-logs', [AdminDealController::class, 'auditLogs']);
            Route::get('disputes', [AdminDisputeController::class, 'index']);
            Route::post('disputes/{dispute}/take', [AdminDisputeController::class, 'take']);
            Route::post('disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve']);
        });
    });
});
