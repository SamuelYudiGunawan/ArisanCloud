<?php

use App\Http\Controllers\DrawController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (none for this app)

// Protected routes - require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    });

    // Group routes
    Route::prefix('groups')->group(function () {
        // List all groups user is a member of
        Route::get('/', [GroupController::class, 'index']);
        
        // Create a new group (with optional member invites)
        Route::post('/', [GroupController::class, 'store']);
        
        // Get group details
        Route::get('/{group}', [GroupController::class, 'show']);
        
        // Update group
        Route::put('/{group}', [GroupController::class, 'update']);
        
        // Delete group
        Route::delete('/{group}', [GroupController::class, 'destroy']);

        // Member management
        Route::post('/{group}/invite', [GroupController::class, 'inviteMember']);
        Route::delete('/{group}/members/{user}', [GroupController::class, 'removeMember']);
        Route::post('/{group}/leave', [GroupController::class, 'leaveGroup']);

        // Period management
        Route::post('/{group}/start-period', [DrawController::class, 'startPeriod']);

        // Payment routes
        Route::post('/{group}/payments', [PaymentController::class, 'store']);
        Route::get('/{group}/payments', [PaymentController::class, 'history']);
        Route::get('/{group}/payments/status', [PaymentController::class, 'currentPeriodStatus']);
        Route::post('/{group}/payments/{payment}/approve', [PaymentController::class, 'approve']);
        Route::post('/{group}/payments/{payment}/reject', [PaymentController::class, 'reject']);

        // Draw routes
        Route::post('/{group}/draw', [DrawController::class, 'draw']);
        Route::get('/{group}/draw-history', [DrawController::class, 'history']);
    });
});

