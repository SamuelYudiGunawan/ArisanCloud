<?php

use App\Http\Controllers\ArisanWebController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('arisan.index');
    }
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    return redirect()->route('arisan.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// Arisan Routes
Route::middleware(['auth', 'verified'])->prefix('arisan')->name('arisan.')->group(function () {
    Route::get('/', [ArisanWebController::class, 'index'])->name('index');
    Route::get('/create', [ArisanWebController::class, 'create'])->name('create');
    Route::post('/', [ArisanWebController::class, 'store'])->name('store');
    Route::get('/{group}', [ArisanWebController::class, 'show'])->name('show');
    
    // Member Management
    Route::post('/{group}/invite', [ArisanWebController::class, 'inviteMember'])->name('invite');
    Route::delete('/{group}/members/{user}', [ArisanWebController::class, 'removeMember'])->name('members.remove');
    
    // Period & Payment
    Route::post('/{group}/start-period', [ArisanWebController::class, 'startPeriod'])->name('start-period');
    Route::post('/{group}/pay', [ArisanWebController::class, 'pay'])->name('pay');
    Route::post('/{group}/payments/{payment}/approve', [ArisanWebController::class, 'approvePayment'])->name('payments.approve');
    Route::post('/{group}/payments/{payment}/reject', [ArisanWebController::class, 'rejectPayment'])->name('payments.reject');
    
    // Draw
    Route::post('/{group}/draw', [ArisanWebController::class, 'draw'])->name('draw');
});

require __DIR__.'/settings.php';
