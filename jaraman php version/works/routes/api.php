<?php

use App\Http\Controllers\API\BankController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\IngredientController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\HelpTicketController;
use App\Http\Controllers\LgaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\VendorCategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('jaram')->group(function () {

    // ── Webhooks (public) ─────────────────────────────────────────────
    Route::post('/webhook/paystack', [PaymentController::class, 'handlePaystackWebhook'])->middleware('paystack-webhook');
    Route::get('/verify-transaction/{slug}', [PaymentController::class, 'verifyTransaction']);

    // ── Public geo ────────────────────────────────────────────────────
    Route::get('/states',              [StateController::class, 'index']);
    Route::get('/states/{state}',      [StateController::class, 'findState']);
    Route::get('/lgas',                [LgaController::class, 'index']);
    Route::get('/lgas/{lga}',          [LgaController::class, 'findLga']);
    Route::get('/country',             [CountryController::class, 'index']);
    Route::get('/country/{c}/states',  [CountryController::class, 'states']);
    Route::get('/vendors/categories',  [ProductController::class, 'getVendorCategories']);

    // ── Guest auth ────────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::post('/register',            [UserController::class, 'registerUser']);
        Route::post('/validate-otp',        [UserController::class, 'validateUserRegisterOTP']);
        Route::post('/validate-email',      [UserController::class, 'verifyEmailWithOTP']);
        Route::post('/resend-otp',          [UserController::class, 'resendOtp']);
        Route::post('/login',               [UserController::class, 'login']);
        Route::post('/forgot-password',     [ForgotPasswordController::class, 'sendResetLink']);
        Route::post('/reset-password',      [ResetPasswordController::class, 'reset']);
        Route::post('/profile-update/{email}',            [UserController::class, 'updateProfile']);
        Route::post('/update-vendor-categories/{email}',  [VendorCategoryController::class, 'store']);
    });

    // ── Authenticated ─────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [UserController::class, 'logout']);

        // Profile
        Route::get('/fetch-user',          [UserController::class, 'fetchUserProfile']);
        Route::post('/update-profile',     [UserController::class, 'editUserProfile']);
        Route::patch('/user/change-password', [UserController::class, 'changePassword']);
        Route::get('/my-referrals',        [UserController::class, 'myRefferals']);

        // FCM token
        Route::post('/fcm-token', function (Request $request) {
            $request->validate(['token' => 'required|string']);
            $request->user()->update(['fcm_token' => $request->token]);
            return response()->json(['status' => true, 'message' => 'FCM token saved']);
        });

        // ── Wallet ────────────────────────────────────────────────────
        Route::get('/fetch-wallet',            [UserController::class, 'fetchUserWallet']); // backward compat
        Route::get('/wallet',                  [WalletController::class, 'balance']);
        Route::get('/wallet/transactions',     [WalletController::class, 'transactions']); // FIX: tx history
        Route::post('/wallet/transfer-to-bank',[WalletController::class, 'transfer']);     // FIX: withdrawal

        // Banks
        Route::get('/banks', [BankController::class, 'index']);

        // Payments
        Route::prefix('payments')->controller(PaymentController::class)->group(function () {
            Route::get('/',                        'all');
            Route::get('/{id}',                    'show');
            Route::post('/initialize-transaction', 'fundWallet');
        });
        Route::get('/transfers', [PaymentController::class, 'getTransfers']);

        // Settings
        Route::get('/settings',  [SettingsController::class, 'index']);
        Route::post('/settings', [SettingsController::class, 'store']);

        // Notifications
        Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
            Route::get('/',           'index');
            Route::post('/{id}/read', 'markAsRead');
            Route::get('/unread-count', fn () => response()->json([
                'unread' => auth()->user()->unreadNotifications()->count()
            ]));
        });

        // ── Customer orders ───────────────────────────────────────────
        Route::prefix('orders')->controller(OrderController::class)->group(function () {
            Route::get('/',                'all');
            Route::get('/{order}',         'show');
            Route::post('/',               'store');
            Route::post('/{order}/cancel', 'cancel');
        });

        // Support / Help
        Route::prefix('support')->group(function () {
            Route::post('/',              [HelpTicketController::class, 'store']);
            Route::get('/',               [HelpTicketController::class, 'index']);
            Route::get('/{id}',           [HelpTicketController::class, 'show']);
            Route::patch('/{id}/status',  [HelpTicketController::class, 'updateStatus']);
        });

        // Favourites
        Route::prefix('favorites')->controller(FavoritesController::class)->group(function () {
            Route::get('/',        'index');
            Route::post('/',       'store');
            Route::delete('/{id}', 'destroy');
        });

        // Addresses
        Route::prefix('addresses')->controller(AddressController::class)->group(function () {
            Route::get('/',           'index');
            Route::post('/',          'store');
            Route::put('/{address}',  'update');
        });

        // Catalogue (read)
        Route::prefix('fetch')->controller(ProductController::class)->group(function () {
            Route::get('/categories-all-products',   'getCategoriesAllProducts');
            Route::get('/categories-limit-products', 'getCategoriesLimitProducts');
            Route::get('/ingredients',               'fetchingredient');
            Route::get('/product',                   'fetchProduct');
            Route::get('/uom',                       'fetchUom');
            Route::get('/product/{id}',              'getProductById');
        });

        Route::get('/advertisements', [AdvertisementController::class, 'fetch_adverts']);
        Route::post('/foods',         [FoodController::class, 'store']);

        // PIN
        Route::prefix('pin')->group(function () {
            Route::post('/set',           [PinController::class, 'setPin']);
            Route::post('/verify',        [PinController::class, 'verifyPin']);
            Route::get('/validate',       [PinController::class, 'validatePinToken']);
            Route::post('/clear',         [PinController::class, 'clearPinToken']);
            Route::post('/request-reset', [PinController::class, 'requestReset']);
            Route::post('/reset',         [PinController::class, 'resetPin']);
        });
    });

    // ── Vendor routes ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'vendor'])->prefix('vendor')->group(function () {
        Route::get('/orders',                        [OrderController::class, 'getAvailableOrders']); // FIX
        Route::get('/orders/accepted',               [OrderController::class, 'myOrders']);           // FIX
        Route::get('/orders/{item_id}',              [OrderController::class, 'showOrderByItemId']);
        Route::post('/orders/item/{item_id}/decision',[OrderController::class, 'decide']);
    });
});
