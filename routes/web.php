<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Shop\CartController as ShopCartController;
use App\Http\Controllers\Shop\CatalogController as ShopCatalogController;
use App\Http\Controllers\Shop\CheckoutController as ShopCheckoutController;
use App\Http\Controllers\Shop\HomeController as ShopHomeController;
use App\Http\Controllers\Shop\OrderController as ShopOrderController;
use App\Http\Controllers\Shop\PaymentController as ShopPaymentController;
use App\Http\Controllers\Shop\PaystackWebhookController as ShopPaystackWebhookController;
use App\Http\Controllers\Services\ServiceController as ServicesController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/signup', function () {
    return view('signup');
})->name('signup');

Route::post('/auth/session-token', function (Illuminate\Http\Request $request) {
    $user = $request->user();

    if ($user === null) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $token = auth('api')->login($user);

    return response()->json([
        'access_token' => $token,
        'user' => $user,
    ])->withCookie(cookie('auth_token', $token, 60 * 24, '/', null, $request->isSecure(), true, false, 'lax'));
})->middleware(['jwt.cookie', 'web.auth'])->name('auth.session-token');

Route::get('/bundles', function () {
    return redirect()->route('services.data');
})->name('bundles');

Route::get('/support', function () {
    $contactEmail = \App\Services\SettingsService::get('contact_email', '');
    $supportWhatsappUrl = \App\Services\SettingsService::get('support_whatsapp_url', '');
    $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
    return view('support', [
        'support_email' => $contactEmail ?: null,
        'support_whatsapp_url' => $supportWhatsappUrl ?: null,
        'support_whatsapp_display' => $contactPhone ?: null,
    ]);
})->middleware(['web.auth', 'feature:support'])->name('support');

Route::get('/forgot-password', function () {
    return view('forgot-password');
})->name('password.request');

Route::get('/dashboard', function () {
    if (\App\Services\FeatureSettings::mallOnly()) {
        return redirect()->route('shop.index');
    }

    return view('dashboard');
})->middleware('web.auth');

Route::get('/orders', function () {
    return view('orders');
})->middleware(['web.auth', 'feature:orders'])->name('orders.index');

Route::get('/orders/shop', function () {
    return view('shop-orders');
})->middleware(['jwt.cookie', 'web.auth', 'feature:shop_orders'])->name('orders.shop');

Route::get('/topup', function () {
    $manualNumber = \App\Services\SettingsService::get('manual_payment_mobile_money', '0241234567');
    $manualName = \App\Services\SettingsService::get('manual_payment_account_name', 'Data2Buy Official');
    $manualTopupEnabled = \App\Services\SettingsService::getBool('manual_topup_enabled');
    return view('topup', [
        'manual_payment_number' => $manualNumber,
        'manual_payment_name' => $manualName,
        'manual_topup_enabled' => $manualTopupEnabled,
    ]);
})->middleware(['web.auth', 'feature:wallet'])->name('topup');

Route::get('/topup/callback', [\App\Http\Controllers\TopUpController::class, 'callback'])->name('topup.callback');

Route::get('/notifications', function () {
    return view('notifications');
})->middleware(['web.auth', 'feature:notifications'])->name('notifications');

Route::get('/profile', function () {
    return view('profile', [
        'deliveryZones' => \App\Shop\Services\ShopSettings::deliveryZones(),
    ]);
})->middleware('web.auth')->name('profile');

Route::get('/referral', function () {
    return view('referral');
})->middleware(['web.auth', 'feature:referral'])->name('referral');

Route::get('/reseller', function () {
    return view('reseller');
})->middleware(['web.auth', 'feature:reseller'])->name('reseller');

Route::get('/reseller/prices', function () {
    return view('reseller-prices');
})->middleware(['web.auth', 'feature:reseller'])->name('reseller.prices');

Route::get('/reseller/withdrawals', function () {
    return view('reseller-withdrawals');
})->middleware(['web.auth', 'feature:reseller'])->name('reseller.withdrawals');

Route::get('/store', function () {
    return view('store-landing');
})->name('store.landing');

Route::get('/store/{slug}', function ($slug) {
    $reseller = \App\Http\Controllers\StorefrontController::resellerBySlug($slug);
    $storeName = null;
    $storeWhatsappUrl = null;
    if ($reseller && $reseller->isReseller() && trim((string) $reseller->store_slug) !== '' && trim((string) $reseller->store_name) !== '' && trim((string) $reseller->store_whatsapp) !== '') {
        $storeName = trim($reseller->store_name);
        $whatsapp = preg_replace('/\D/', '', (string) $reseller->store_whatsapp);
        $storeWhatsappUrl = $whatsapp !== '' ? 'https://wa.me/' . (str_starts_with($whatsapp, '233') ? $whatsapp : '233' . ltrim($whatsapp, '0')) : null;
    }
    return view('storefront', [
        'slug' => $slug,
        'store_name' => $storeName,
        'store_whatsapp_url' => $storeWhatsappUrl,
    ]);
})->name('storefront.show');

Route::get('/store/{slug}/callback', [\App\Http\Controllers\StorefrontController::class, 'callback'])->name('storefront.callback');

Route::get('/privacy', function () {
    return view('privacy');
})->name('legal.privacy');

Route::get('/terms', function () {
    return view('terms');
})->name('legal.terms');

Route::get('/returns', function () {
    return view('shop.legal.returns');
})->name('legal.returns');

Route::get('/contact', function () {
    return view('shop.legal.contact');
})->name('legal.contact');

Route::get('/maintenance', function () {
    return view('maintenance');
})->name('maintenance');

if (! app()->environment('production')) {
    Route::view('/design-system', 'design-system')->name('design-system');
}

Route::post('/logout', function (Illuminate\Http\Request $request) {
    try {
        auth('api')->logout();
    } catch (\Throwable) {
        // Token already invalid.
    }

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Signed out.'])->withoutCookie('auth_token');
})->name('logout');

// Shop (guest checkout, physical goods) — wired into the data system.
Route::prefix('shop')->name('shop.')->middleware(['jwt.cookie', 'feature:shop'])->group(function () {
    Route::get('/', ShopHomeController::class)->name('index');
    Route::get('/search', [ShopCatalogController::class, 'search'])->name('search');
    Route::get('/category/{category:slug}', [ShopCatalogController::class, 'category'])->name('categories.show');
    Route::get('/product/{product:slug}', [ShopCatalogController::class, 'product'])->name('products.show');
    Route::view('/more', 'shop.more')->name('more');

    Route::middleware(['web.auth', 'throttle:30,1'])->group(function () {
        Route::get('/cart', [ShopCartController::class, 'show'])->name('cart.show');
        Route::post('/cart/items', [ShopCartController::class, 'add'])->name('cart.items.store');
        Route::patch('/cart/items/{item}', [ShopCartController::class, 'update'])->name('cart.items.update');
        Route::delete('/cart/items/{item}', [ShopCartController::class, 'remove'])->name('cart.items.destroy');
        Route::delete('/cart', [ShopCartController::class, 'clear'])->name('cart.clear');

        Route::get('/checkout', [ShopCheckoutController::class, 'show'])->name('checkout.show');
        Route::post('/checkout', [ShopCheckoutController::class, 'store'])->name('checkout.store');
    });

    Route::get('/track', fn () => redirect()->route('orders.shop'))->middleware('web.auth')->name('track');
    Route::get('/orders/{order:order_number}', [ShopOrderController::class, 'show'])->middleware('signed')->name('orders.show');

    Route::get('/payments/callback', [ShopPaymentController::class, 'callback'])->name('payments.callback');
    Route::get('/payments/{order:order_number}', [ShopPaymentController::class, 'pay'])->middleware('signed')->name('payments.pay');

    Route::post('/webhooks/paystack', ShopPaystackWebhookController::class)->name('webhooks.paystack');
});
// PATUPAA services (data, WAEC, utility, subscriptions, AFA) - login required.
Route::prefix('services')->name('services.')->group(function () {
    Route::get('/', [ServicesController::class, 'hub'])->name('index');
    Route::get('/data', [ServicesController::class, 'data'])->middleware('feature:data')->name('data');
    Route::get('/waec', [ServicesController::class, 'waec'])->middleware('feature:waec')->name('waec');
    Route::get('/utility', [ServicesController::class, 'utility'])->middleware('feature:utility')->name('utility');
    Route::get('/subscriptions', [ServicesController::class, 'subscriptions'])->middleware('feature:subscriptions')->name('subscriptions');
    Route::get('/afa', [ServicesController::class, 'afa'])->middleware('feature:afa')->name('afa');

    Route::middleware(['web.auth', 'throttle:20,1'])->group(function () {
        Route::post('/data/{item}', [ServicesController::class, 'storeData'])->middleware('feature:data')->name('data.store');
        Route::post('/waec/{item}', [ServicesController::class, 'storeWaec'])->middleware('feature:waec')->name('waec.store');
        Route::post('/utility/lookup', [ServicesController::class, 'lookupUtility'])->middleware('feature:utility')->name('utility.lookup');
        Route::post('/utility/{item}', [ServicesController::class, 'storeUtility'])->middleware('feature:utility')->name('utility.store');
        Route::post('/subscriptions/{item}', [ServicesController::class, 'storeSubscriptions'])->middleware('feature:subscriptions')->name('subscriptions.store');
        Route::post('/afa', [ServicesController::class, 'storeAfa'])->middleware('feature:afa')->name('afa.store');
        Route::get('/orders', [ServicesController::class, 'orders'])->middleware('feature:service_orders')->name('orders');
        Route::get('/orders/{reference}', [ServicesController::class, 'orderShow'])->middleware('feature:service_orders')->name('orders.show');
    });
});

// Admin Routes
Route::prefix('admin')->middleware(['jwt.cookie', 'admin.web'])->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard'); // Let Alpine handling the redirect if needed, or just show dashboard
    });

    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/users', function () {
        return view('admin.users');
    })->name('admin.users');

    Route::get('/packages', function () {
        return view('admin.packages');
    })->name('admin.packages');

    Route::get('/orders', function () {
        return view('admin.orders');
    })->name('admin.orders');

    Route::get('/shop', function () {
        return view('admin.shop');
    })->name('admin.shop');

    Route::get('/services', function () {
        return view('admin.services');
    })->name('admin.services');

    Route::get('/topups', function () {
        return view('admin.topups');
    })->name('admin.topups');

    Route::get('/payments', function () {
        return view('admin.payments');
    })->name('admin.payments');

    Route::get('/support', function () {
        return view('admin.support');
    })->name('admin.support');

    Route::get('/complaints', function () {
        return view('admin.support');
    })->name('admin.complaints.index');

    Route::get('/notifications', function () {
        return view('admin.notifications');
    })->name('admin.notifications');

    Route::get('/announcements', function () {
        return view('admin.announcements');
    })->name('admin.announcements');

    Route::get('/settings', function () {
        return view('admin.settings');
    })->name('admin.settings');

    Route::get('/resellers', function () {
        return view('admin.resellers');
    })->name('admin.resellers');

    Route::get('/affiliates', function () {
        return view('admin.affiliates');
    })->name('admin.affiliates');

    Route::get('/spin-preview', function () {
        return view('admin.spin-preview');
    })->name('admin.spin-preview');

    Route::get('/statistics', function () {
        return view('admin.statistics');
    })->name('admin.statistics');
});
