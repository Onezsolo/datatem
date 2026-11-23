<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/signup', function () {
    return view('signup');
});

Route::get('/bundles', function () {
    return view('bundles');
});

Route::get('/support', function () {
    $contactEmail = \App\Services\SettingsService::get('contact_email', '');
    $supportWhatsappUrl = \App\Services\SettingsService::get('support_whatsapp_url', '');
    $contactPhone = \App\Services\SettingsService::get('contact_phone', '');
    return view('support', [
        'support_email' => $contactEmail ?: null,
        'support_whatsapp_url' => $supportWhatsappUrl ?: null,
        'support_whatsapp_display' => $contactPhone ?: null,
    ]);
});

Route::get('/forgot-password', function () {
    return view('forgot-password');
});

Route::get('/dashboard', function () {
    return view('dashboard');
});

Route::get('/orders', function () {
    return view('orders');
})->name('orders.index');

Route::get('/topup', function () {
    $manualNumber = \App\Services\SettingsService::get('manual_payment_mobile_money', '0241234567');
    $manualName = \App\Services\SettingsService::get('manual_payment_account_name', 'Data2Buy Official');
    $manualTopupEnabled = \App\Services\SettingsService::getBool('manual_topup_enabled');
    return view('topup', [
        'manual_payment_number' => $manualNumber,
        'manual_payment_name' => $manualName,
        'manual_topup_enabled' => $manualTopupEnabled,
    ]);
});

Route::get('/topup/callback', [\App\Http\Controllers\TopUpController::class, 'callback'])->name('topup.callback');

Route::get('/notifications', function () {
    return view('notifications');
});

Route::get('/profile', function () {
    return view('profile');
});

Route::get('/referral', function () {
    return view('referral');
})->name('referral');

Route::get('/reseller', function () {
    return view('reseller');
})->name('reseller');

Route::get('/reseller/prices', function () {
    return view('reseller-prices');
})->name('reseller.prices');

Route::get('/reseller/withdrawals', function () {
    return view('reseller-withdrawals');
})->name('reseller.withdrawals');

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
});

Route::get('/terms', function () {
    return view('terms');
});

Route::get('/maintenance', function () {
    return view('maintenance');
})->name('maintenance');

// Admin Routes
Route::prefix('admin')->group(function () {
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
