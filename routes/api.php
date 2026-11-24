<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public storefront (no auth)
Route::get('/store/{slug}/packages', [\App\Http\Controllers\StorefrontController::class, 'packages']);
Route::post('/store/{slug}/checkout', [\App\Http\Controllers\StorefrontController::class, 'checkout']);
Route::get('/store/{slug}/track', [\App\Http\Controllers\StorefrontController::class, 'trackOrders']);
// Paystack webhook for storefront (no auth, no CSRF on api routes)
Route::post('/webhook/paystack-storefront', [\App\Http\Controllers\StorefrontController::class, 'paystackWebhook']);

Route::group([
    'prefix' => 'auth'
], function () {
    // Public routes
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected routes
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/user-profile', [AuthController::class, 'userProfile']);
        Route::put('/user/profile', [\App\Http\Controllers\UserController::class, 'updateProfile']);
        Route::put('/user/delivery-details', [\App\Http\Controllers\UserController::class, 'updateDeliveryDetails']);
        Route::put('/user/password', [\App\Http\Controllers\UserController::class, 'changePassword']);
        Route::delete('/user/account', [\App\Http\Controllers\UserController::class, 'deleteAccount']);
        Route::get('/user/stats', [\App\Http\Controllers\UserController::class, 'getStats']);
        Route::get('/user/transactions', [\App\Http\Controllers\UserController::class, 'getTransactions']);
        Route::get('/user/popular-packages', [\App\Http\Controllers\UserController::class, 'getPopularPackages']);
        Route::get('/user/notifications', [\App\Http\Controllers\UserController::class, 'getNotifications']);
        Route::post('/user/notifications/{id}/read', [\App\Http\Controllers\UserController::class, 'markNotificationRead']);
        Route::post('/user/notifications/read-all', [\App\Http\Controllers\UserController::class, 'markAllNotificationsRead']);
        // Buy data: list packages, single purchase, bulk purchase
        Route::get('/buy-data', [\App\Http\Controllers\BuyDataController::class, 'index']);
        Route::post('/buy-data', [\App\Http\Controllers\BuyDataController::class, 'store']);
        Route::post('/buy-data/bulk', [\App\Http\Controllers\BuyDataController::class, 'storeBulk']);
        // User orders and complaints
        Route::get('/orders', [\App\Http\Controllers\OrdersController::class, 'index']);
        Route::get('/shop-orders', [\App\Http\Controllers\Shop\MemberOrderController::class, 'index']);
        Route::post('/orders/complaint', [\App\Http\Controllers\OrdersController::class, 'storeComplaint']);
        // Wallet top-up via Paystack
        Route::post('/topup/initialize', [\App\Http\Controllers\TopUpController::class, 'initialize']);
        Route::post('/topup/{reference}/verify-paystack', [\App\Http\Controllers\TopUpController::class, 'verifyOwnPaystackPayment']);
        // Reseller request
        Route::get('/reseller', [\App\Http\Controllers\ResellerController::class, 'index']);
        Route::post('/reseller', [\App\Http\Controllers\ResellerController::class, 'store']);
        // Reseller prices (approved resellers only)
        Route::get('/reseller/packages', [\App\Http\Controllers\ResellerController::class, 'packages']);
        Route::post('/reseller/packages/{id}/price', [\App\Http\Controllers\ResellerController::class, 'savePrice']);
        Route::put('/reseller/store-setup', [\App\Http\Controllers\ResellerController::class, 'updateStoreSetup']);
        Route::post('/reseller/withdraw', [\App\Http\Controllers\ResellerController::class, 'requestWithdrawal']);
        // Affiliate (user-facing): dashboard + request withdrawal
        Route::get('/affiliate', [\App\Http\Controllers\AffiliateController::class, 'index']);
        Route::post('/affiliate/withdraw', [\App\Http\Controllers\AffiliateController::class, 'requestWithdrawal']);
    });
});

// Admin management routes
Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth:api', 'admin']
], function () {
    Route::get('/users', [\App\Http\Controllers\AdminController::class, 'getUsers']);
    Route::post('/users', [\App\Http\Controllers\AdminController::class, 'storeUser']);
    Route::put('/users/{id}', [\App\Http\Controllers\AdminController::class, 'updateUser']);
    Route::delete('/users/{id}', [\App\Http\Controllers\AdminController::class, 'deleteUser']);
    Route::post('/users/{id}/balance', [\App\Http\Controllers\AdminController::class, 'adjustBalance']);
    
    Route::get('/stats', [\App\Http\Controllers\AdminController::class, 'getDashboardStats']);
    Route::get('/recent-activity', [\App\Http\Controllers\AdminController::class, 'getRecentActivity']);
    Route::get('/finance-statistics', [\App\Http\Controllers\AdminController::class, 'getFinanceStatistics']);
    Route::get('/profit-calc-snapshot', [\App\Http\Controllers\AdminController::class, 'getProfitCalcSnapshot']);
    Route::post('/profit-calc-snapshot', [\App\Http\Controllers\AdminController::class, 'saveProfitCalcSnapshot']);
    Route::get('/orders', [\App\Http\Controllers\AdminController::class, 'getOrders']);
    Route::post('/orders/check-all-status', [\App\Http\Controllers\AdminController::class, 'checkAllOrdersStatus']);
    Route::post('/orders/{id}/resend', [\App\Http\Controllers\AdminController::class, 'resendOrder']);
    Route::post('/orders/{id}/check-status', [\App\Http\Controllers\AdminController::class, 'checkOrderStatus']);
    Route::get('/packages', [\App\Http\Controllers\AdminController::class, 'getPackages']);
    Route::post('/packages/sync', [\App\Http\Controllers\AdminController::class, 'syncPackages']);
    Route::match(['put', 'patch'], '/packages/{id}', [\App\Http\Controllers\AdminController::class, 'updatePackage']);

    // Shop catalog and shop orders
    Route::get('/shop/categories', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'categories']);
    Route::post('/shop/categories', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'storeCategory']);
    Route::put('/shop/categories/{category}', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'updateCategory']);
    Route::delete('/shop/categories/{category}', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'destroyCategory']);
    Route::get('/shop/products', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'products']);
    Route::post('/shop/products', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'storeProduct']);
    Route::put('/shop/products/{product}', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'updateProduct']);
    Route::delete('/shop/products/{product}', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'destroyProduct']);
    Route::post('/shop/products/{product}/stock', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'adjustStock']);
    Route::post('/shop/products/{product}/images', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'uploadImage']);
    Route::delete('/shop/images/{image}', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'destroyImage']);
    Route::post('/shop/products/{product}/variants', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'storeVariant']);
    Route::put('/shop/variants/{variant}', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'updateVariant']);
    Route::delete('/shop/variants/{variant}', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'destroyVariant']);
    Route::get('/shop/delivery', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'deliverySettings']);
    Route::put('/shop/delivery', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'updateDeliverySettings']);
    Route::get('/shop/orders', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'orders']);
    Route::put('/shop/orders/{order}/status', [\App\Http\Controllers\Shop\AdminCatalogController::class, 'updateOrderStatus']);
    Route::get('/topups', [\App\Http\Controllers\AdminController::class, 'getTopups']);    Route::post('/topups/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveTopup']);
    Route::post('/payments/{reference}/verify-paystack', [\App\Http\Controllers\AdminController::class, 'verifyPaystackPayment']);
    Route::get('/payments', [\App\Http\Controllers\AdminController::class, 'getPayments']);
    Route::get('/tickets', [\App\Http\Controllers\AdminController::class, 'getTickets']);
    Route::get('/resellers', [\App\Http\Controllers\AdminController::class, 'getResellers']);
    Route::post('/resellers/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveReseller']);
    Route::post('/resellers/{id}/reject', [\App\Http\Controllers\AdminController::class, 'rejectReseller']);
    Route::get('/settings', [\App\Http\Controllers\AdminController::class, 'getSettings']);
    Route::put('/settings', [\App\Http\Controllers\AdminController::class, 'updateSettings']);
    Route::post('/settings', [\App\Http\Controllers\AdminController::class, 'updateSettings']); // multipart (logo/favicon upload)
    Route::post('/settings/test-email', [\App\Http\Controllers\AdminController::class, 'testEmail']);
    Route::get('/announcements/recipients', [\App\Http\Controllers\AdminController::class, 'getUsersForAnnouncement']);
    Route::post('/announcements', [\App\Http\Controllers\AdminController::class, 'sendAnnouncement']);
    Route::get('/affiliates/eligible-users', [\App\Http\Controllers\AdminController::class, 'getAffiliateEligibleUsers']);
    Route::get('/affiliates', [\App\Http\Controllers\AdminController::class, 'getAffiliates']);
    Route::post('/affiliates', [\App\Http\Controllers\AdminController::class, 'storeAffiliate']);
    Route::put('/affiliates/{id}', [\App\Http\Controllers\AdminController::class, 'updateAffiliate']);
    Route::get('/affiliate-withdrawals', [\App\Http\Controllers\AdminController::class, 'getAffiliateWithdrawals']);
    Route::get('/affiliate-withdrawals/{id}/detail', [\App\Http\Controllers\AdminController::class, 'getAffiliateWithdrawalDetail']);
    Route::post('/affiliate-withdrawals/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveAffiliateWithdrawal']);
    Route::post('/affiliate-withdrawals/{id}/reject', [\App\Http\Controllers\AdminController::class, 'rejectAffiliateWithdrawal']);
    Route::get('/reseller-withdrawals', [\App\Http\Controllers\AdminController::class, 'getResellerWithdrawals']);
    Route::get('/reseller-withdrawals/{id}/detail', [\App\Http\Controllers\AdminController::class, 'getResellerWithdrawalDetail']);
    Route::post('/reseller-withdrawals/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveResellerWithdrawal']);
    Route::post('/reseller-withdrawals/{id}/reject', [\App\Http\Controllers\AdminController::class, 'rejectResellerWithdrawal']);
});
