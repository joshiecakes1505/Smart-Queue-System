<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ServiceCategoryController as AdminServiceCategoryController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\FrontDesk\QueueController as FrontDeskQueueController;
use App\Http\Controllers\Cashier\CashierController as CashierController;
use App\Http\Controllers\Public\PublicQueueController as PublicQueueController;
use App\Http\Controllers\Display\DisplayController as DisplayController;
use App\Http\Controllers\Api\QRCodeController as QRCodeController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

Route::get('/', function () {
    // If already authenticated, redirect to appropriate dashboard
    $authenticatedUser = Auth::guard('admin')->user()
        ?? Auth::guard('frontdesk')->user()
        ?? Auth::guard('cashier')->user()
        ?? Auth::guard('web')->user();

    if ($authenticatedUser) {
        $roleName = $authenticatedUser->role?->name;
        return match ($roleName) {
            'admin' => redirect()->route('admin.dashboard'),
            'frontdesk' => redirect()->route('frontdesk.queues.index'),
            'cashier' => redirect()->route('cashier.index'),
            default => redirect()->route('login'),
        };
    }
    
    // Otherwise show landing page
    return Inertia::render('Landing');
})->name('landing');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth:admin,frontdesk,cashier,web', 'verified'])->name('dashboard');

Route::middleware('auth:admin,frontdesk,cashier,web')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Admin routes
Route::middleware(['auth:admin', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('cashier-windows/{cashierWindow}/assign', [AdminUserController::class, 'assignCashier'])->name('cashier-windows.assign');
    Route::resource('users', AdminUserController::class)->except(['show']);
    Route::resource('service-categories', AdminServiceCategoryController::class)->except(['show']);
    Route::get('reports/daily', [AdminReportController::class, 'daily'])->name('reports.daily');
});

// Frontdesk routes (register queue)
Route::middleware(['auth:frontdesk', 'role:frontdesk'])->prefix('frontdesk')->name('frontdesk.')->group(function () {
    Route::get('queues', [FrontDeskQueueController::class, 'index'])->name('queues.index');
    Route::post('queues', [FrontDeskQueueController::class, 'store'])->name('queues.store');
    Route::get('queues/{queue}/print', [FrontDeskQueueController::class, 'print'])->name('queues.print');
});

Route::middleware(['auth:frontdesk', 'role:frontdesk'])->group(function () {
    Route::post('/queues/{queue}/print', [FrontDeskQueueController::class, 'printReceipt'])
        ->name('frontdesk.queues.print-receipt');
});

// Cashier routes
Route::middleware(['auth:cashier', 'role:cashier'])->prefix('cashier')->name('cashier.')->group(function () {
    Route::get('/', [CashierController::class, 'index'])->name('index');
    Route::post('call-next', [CashierController::class, 'callNext'])->name('callNext');
    Route::post('{queue}/skip', [CashierController::class, 'skip'])->name('skip');
    Route::post('{queue}/recall', [CashierController::class, 'recall'])->name('recall');
    Route::post('{queue}/complete', [CashierController::class, 'complete'])->name('complete');
    Route::post('queue/{queue}/reinstate', [CashierController::class, 'reinstate'])->name('reinstate');
});

// Public endpoints
Route::get('/public/live', [PublicQueueController::class, 'liveView'])->name('public.live');
Route::get('/queue/{queue_number}', [PublicQueueController::class, 'showQueueByNumber'])->name('public.queue.show');
Route::get('/public/queue/{queue_number}', function (string $queue_number) {
    return redirect()->route('public.queue.show', ['queue_number' => $queue_number], 301);
})->name('public.queue.legacy');
Route::get('/api/queue/{queue_number}/status', [PublicQueueController::class, 'getQueueData'])->name('api.queue.status');

// Display
Route::get('/display', [DisplayController::class, 'index'])->name('display.index');
Route::get('/display/data', [DisplayController::class, 'data'])->name('display.data');

// QR Code endpoints (public)
Route::get('/qr/{queueNumber}', [QRCodeController::class, 'generate'])->name('qr.generate');
Route::get('/qr/{queueNumber}/data', [QRCodeController::class, 'data'])->name('qr.data');

