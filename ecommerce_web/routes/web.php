<?php

use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RevenueReportController;

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'viewall'])->name('products.viewall');
Route::get('/products/{id}', [ProductController::class, 'detail'])->name('products.detail');
Route::post('/products/{id}/review', [ProductController::class, 'addReview'])->name('products.review');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [CartController::class, 'processCheckout'])->name('checkout.process');

// Legacy web routes for backward compatibility
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/orders/track', [OrderController::class, 'trackOrders'])->name('orders.track');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Routes cho Admin - Chỉ admin mới có thể truy cập
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [AdminController::class, 'index'])->name('index');
    
    // Category CRUD routes
    Route::get('/categories', [AdminController::class, 'categories'])->name('categories.index');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
    
    // Product CRUD routes
    Route::get('/products', [AdminController::class, 'products'])->name('products.index');
    Route::post('/products', [AdminController::class, 'storeProduct'])->name('products.store');
    Route::put('/products/{product}', [AdminController::class, 'updateProduct'])->name('products.update');
    Route::delete('/products/{product}', [AdminController::class, 'destroyProduct'])->name('products.destroy');
    
    // Order management routes for admin
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/statistics/overview', [OrderController::class, 'statistics'])->name('statistics');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
        Route::post('/{order}/status', [OrderController::class, 'updateStatus'])->name('update-status');
    });
    
    // Revenue Report routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/revenue', [RevenueReportController::class, 'index'])->name('revenue.index');
        Route::get('/revenue-data', [RevenueReportController::class, 'getReportData'])->name('revenue.data');
        Route::post('/send-revenue-report', [RevenueReportController::class, 'sendEmailReport'])->name('revenue.email');
    });
});


Route::get('/dashboard', function () {
    $user = Auth::user();
    
    if ($user && $user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    
    return redirect()->route('home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/lang',  [LanguageController::class, 'changeLanguage'])->name('lang.set');
Route::get('/language/{language}', [LanguageController::class, 'changeLanguageBlade'])->name('language.change'); 

Route::post('/products/{id}/reviews', [ReviewController::class, 'store'])
    ->name('reviews.store')
    ->middleware('auth');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';