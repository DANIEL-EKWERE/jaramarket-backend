<?php

use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\VendorManagementController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FranchiseController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StateRepresentativeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Guest only ─────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::redirect('/', '/login');
    Route::get('/login',   fn () => view('auth.login'))->name('login.show');
    Route::post('/login',  [AuthController::class, 'login'])->name('login');
    Route::get('/register',  fn () => view('auth.register'))->name('register.show');
    Route::post('/register', [AuthController::class, 'register'])->name('register');

    Route::prefix('password')->group(function () {
        Route::get('/forgot',        [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('/forgot',       [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset',        [ResetPasswordController::class, 'reset'])->name('password.update');
    });
});

// ── Email verification ──────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('email')->group(function () {
    Route::get('/verify',              [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify/{id}/{hash}',  [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
});

// ── Admin dashboard ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/admin/profile',  [AuthController::class, 'profilePage'])->name('admin.profile');
    Route::put('/admin/profile',  [AuthController::class, 'updateProfile'])->name('admin.profile.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/panel',          [NotificationController::class, 'panel'])->name('notifications.panel');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        Route::get('/{id}/read',      [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    });

    // ── Orders ────────────────────────────────────────────────────────
    Route::middleware('permission:view_orders')->prefix('orders')->group(function () {
        Route::get('/',          [OrderController::class, 'index'])->name('orders.index');
        Route::get('/data',      [OrderController::class, 'getData'])->name('orders.data');
        Route::get('/create',    [OrderController::class, 'create'])->name('orders.create');
        Route::post('/',         [OrderController::class, 'store'])->name('orders.store');
        Route::get('/{order}',   [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->middleware('permission:manage_orders')->name('orders.update.status');
        Route::delete('/{order}',       [OrderController::class, 'destroy'])->middleware('permission:manage_orders')->name('orders.destroy');
    });

    // ── Categories ────────────────────────────────────────────────────
    Route::middleware('permission:view_categories')->prefix('categories')->group(function () {
        Route::get('/',                [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/data',            [CategoryController::class, 'getData'])->name('categories.data');
        Route::get('/create',          [CategoryController::class, 'create'])->middleware('permission:manage_categories')->name('categories.create');
        Route::post('/',               [CategoryController::class, 'store'])->middleware('permission:manage_categories')->name('categories.store');
        Route::get('/{category}',      [CategoryController::class, 'show'])->name('categories.show');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->middleware('permission:manage_categories')->name('categories.edit');
        Route::put('/{category}',      [CategoryController::class, 'update'])->middleware('permission:manage_categories')->name('categories.update');
        Route::delete('/{category}',   [CategoryController::class, 'destroy'])->middleware('permission:manage_categories')->name('categories.destroy');
    });

    // ── Products ──────────────────────────────────────────────────────
    Route::middleware('permission:view_products')->prefix('products')->group(function () {
        Route::get('/',               [ProductController::class, 'index'])->name('products.index');
        Route::get('/data',           [ProductController::class, 'getData'])->name('products.data');
        Route::get('/create',         [ProductController::class, 'create'])->middleware('permission:manage_products')->name('products.create');
        Route::post('/',              [ProductController::class, 'store'])->middleware('permission:manage_products')->name('products.store');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->middleware('permission:manage_products')->name('products.edit');
        Route::put('/{product}',      [ProductController::class, 'update'])->middleware('permission:manage_products')->name('products.update');
        Route::delete('/{product}',   [ProductController::class, 'destroy'])->middleware('permission:manage_products')->name('products.destroy');
    });

    // ── Ingredients ───────────────────────────────────────────────────
    Route::middleware('permission:view_ingredients')->group(function () {
        Route::get('/ingredients/data', [IngredientController::class, 'getData'])->name('ingredients.data');
        Route::resource('ingredients', IngredientController::class);
    });

    // ── Users / Customers ─────────────────────────────────────────────
    Route::middleware('permission:view_users')->prefix('users')->group(function () {
        Route::get('/',                     [UserController::class, 'index'])->name('users.index');
        Route::get('/data',                 [UserController::class, 'getData'])->name('users.data');
        Route::get('/create',               [UserController::class, 'create'])->middleware('permission:manage_users')->name('users.create');
        Route::post('/',                    [UserController::class, 'store'])->middleware('permission:manage_users')->name('users.store');
        Route::get('/{user}/edit',          [UserController::class, 'edit'])->middleware('permission:manage_users')->name('users.edit');
        Route::put('/{user}',               [UserController::class, 'update'])->middleware('permission:manage_users')->name('users.update');
        Route::put('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('permission:manage_users')->name('users.toggle.status');
        Route::delete('/{user}',            [UserController::class, 'destroy'])->middleware('permission:manage_users')->name('users.destroy');
    });

    // ── Vendor Management ─────────────────────────────────────────────
    Route::middleware('permission:view_vendors')->prefix('vendors')->name('admin.vendors.')->group(function () {
        Route::get('/',                        [VendorManagementController::class, 'index'])->name('index');
        Route::get('/data',                    [VendorManagementController::class, 'getData'])->name('data');
        Route::get('/{vendor}',                [VendorManagementController::class, 'show'])->name('show');
        Route::get('/{vendor}/orders',         [VendorManagementController::class, 'vendorOrders'])->name('orders');
        Route::patch('/{vendor}/toggle-status',       [VendorManagementController::class, 'toggleStatus'])->middleware('permission:manage_vendors')->name('toggle-status');
        Route::patch('/{vendor}/toggle-verification', [VendorManagementController::class, 'toggleVerification'])->middleware('permission:manage_vendors')->name('toggle-verification');
    });

    // ── Admin Management (super_admin) ────────────────────────────────
    Route::middleware('permission:manage_admins')->prefix('admin-management')->name('admin-management.')->group(function () {
        Route::get('/',                              [AdminManagementController::class, 'index'])->name('index');
        Route::get('/create',                        [AdminManagementController::class, 'create'])->name('create');
        Route::post('/',                             [AdminManagementController::class, 'store'])->name('store');
        Route::get('/{admin}/edit',                  [AdminManagementController::class, 'edit'])->name('edit');
        Route::put('/{admin}',                       [AdminManagementController::class, 'update'])->name('update');
        Route::delete('/{admin}',                    [AdminManagementController::class, 'destroy'])->name('destroy');
        Route::patch('/{admin}/toggle-status',       [AdminManagementController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{admin}/reset-permissions',    [AdminManagementController::class, 'resetPermissions'])->name('reset-permissions');
    });

    // ── Finance ───────────────────────────────────────────────────────
    Route::prefix('finance')->name('admin.finance.')->group(function () {
        Route::get('/transactions',              [FinanceController::class, 'transactions'])->name('transactions');
        Route::get('/transactions/data',         [FinanceController::class, 'getTransactionsData'])->name('transactions.data');
        Route::get('/wallets',                   [FinanceController::class, 'wallets'])->name('wallets');
        Route::get('/wallets/data',              [FinanceController::class, 'getWalletsData'])->name('wallets.data');
        Route::get('/withdrawals',               [FinanceController::class, 'withdrawals'])->name('withdrawals');
        Route::get('/withdrawals/data',          [FinanceController::class, 'getWithdrawalsData'])->name('withdrawals.data');
        Route::get('/users/{userId}/transactions',[FinanceController::class, 'userTransactions'])->name('user-transactions');
    });

    // ── Commissions ───────────────────────────────────────────────────
    Route::middleware('permission:view_commissions')->prefix('commissions')->group(function () {
        Route::get('/',                [CommissionController::class, 'index'])->name('commissions.index');
        Route::get('/create',          [CommissionController::class, 'create'])->middleware('permission:manage_commissions')->name('commissions.create');
        Route::post('/',               [CommissionController::class, 'store'])->middleware('permission:manage_commissions')->name('commissions.store');
        Route::get('/{c}/edit',        [CommissionController::class, 'edit'])->middleware('permission:manage_commissions')->name('commissions.edit');
        Route::put('/{c}',             [CommissionController::class, 'update'])->middleware('permission:manage_commissions')->name('commissions.update');
        Route::delete('/{c}',          [CommissionController::class, 'destroy'])->middleware('permission:manage_commissions')->name('commissions.destroy');
    });

    // ── Reports ───────────────────────────────────────────────────────
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/summary',                 [ReportController::class, 'index'])->name('summary');
        Route::get('/summary/data',            [ReportController::class, 'getSummary'])->name('summary.data');
        Route::get('/reports/orders',          [ReportController::class, 'orders'])->name('reports.orders');
        Route::get('/reports/orders/export',   [ReportController::class, 'exportOrders'])->name('reports.orders.export');
        Route::get('/reports/products',        [ReportController::class, 'products'])->name('reports.products');
        Route::get('/reports/payments',        [PaymentReportController::class, 'index'])->name('reports.payments');
        Route::get('/reports/payments/export', [PaymentReportController::class, 'export'])->name('reports.payments.export');
    });

    // ── Settings ──────────────────────────────────────────────────────
    Route::middleware('permission:manage_settings')->prefix('settings')->group(function () {
        Route::get('/',  [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/', [SettingsController::class, 'update'])->name('settings.update');
    });

    // ── Other resources ───────────────────────────────────────────────
    Route::resource('advertisements', AdvertisementController::class);

    Route::prefix('representatives')->group(function () {
        Route::get('/',                         [StateRepresentativeController::class, 'index'])->name('representatives.index');
        Route::get('/create',                   [StateRepresentativeController::class, 'create'])->name('representatives.create');
        Route::post('/',                        [StateRepresentativeController::class, 'store'])->name('representatives.store');
        Route::get('/{rep}/edit',               [StateRepresentativeController::class, 'edit'])->name('representatives.edit');
        Route::put('/{rep}',                    [StateRepresentativeController::class, 'update'])->name('representatives.update');
        Route::delete('/{rep}',                 [StateRepresentativeController::class, 'destroy'])->name('representatives.destroy');
        Route::patch('/{rep}/toggle-status',    [StateRepresentativeController::class, 'toggleStatus'])->name('representatives.toggle-status');
    });

    Route::prefix('franchises')->group(function () {
        Route::get('/',              [FranchiseController::class, 'index'])->name('franchises.index');
        Route::get('/create',        [FranchiseController::class, 'create'])->name('franchises.create');
        Route::post('/',             [FranchiseController::class, 'store'])->name('franchises.store');
        Route::get('/{f}/edit',      [FranchiseController::class, 'edit'])->name('franchises.edit');
        Route::put('/{f}',           [FranchiseController::class, 'update'])->name('franchises.update');
        Route::delete('/{f}',        [FranchiseController::class, 'destroy'])->name('franchises.destroy');
    });

    // Payments legacy web view
    Route::prefix('reports/payments')->group(function () {
        Route::get('/',          [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    });
});
