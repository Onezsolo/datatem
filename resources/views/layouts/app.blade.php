<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @php
        $siteName = \App\Services\SettingsService::get('site_name', config('app.name', 'R2BMarket'));
        $siteDescription = \App\Services\SettingsService::get('site_description', 'R2BMarket – Affordable mobile data bundles for MTN, AirtelTigo & Telecel in Ghana. Top up your wallet and get instant delivery.');
        $siteLogoUrl = request()->is('/') ? asset('assets/l1.png') : \App\Services\SettingsService::getLogoUrl();
        $siteFaviconUrl = \App\Services\SettingsService::getFaviconUrl();
    @endphp
    <title>@yield('title', $siteName)</title>
    <meta name="description" content="@yield('meta_description', $siteDescription)">
    <meta name="robots" content="@yield('meta_robots', 'index, follow')">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ $siteFaviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $siteFaviconUrl }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', $siteName . ' – Mobile Data Bundles')">
    <meta property="og:description" content="@yield('og_description', $siteDescription)">
    <meta property="og:image" content="{{ url($siteLogoUrl) }}">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('twitter_title', $siteName . ' – Mobile Data Bundles')">
    <meta name="twitter:description" content="@yield('twitter_description', $siteDescription)">
    <meta name="twitter:image" content="{{ url($siteLogoUrl) }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @if(request()->is('/'))
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    @else
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @endif

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @php
        $themePrimary = \App\Services\ThemeColorService::getPrimaryShades();
        $themeGradientStart = \App\Services\ThemeColorService::getGradientStart();
        $themeGradientEnd = \App\Services\ThemeColorService::getGradientEnd();
    @endphp
    <script>
        document.addEventListener('alpine:init', () => {
            (function() {
                var st = (localStorage.getItem('auth_storage') === 'session') ? sessionStorage : localStorage;
                var u;
                try { u = JSON.parse(st.getItem('user') || '{}'); } catch (e) { u = {}; }
                Alpine.store('auth', {
                    getStorage: function() {
                        return (localStorage.getItem('auth_storage') === 'session') ? sessionStorage : localStorage;
                    },
                    user: u,
                    token: st.getItem('token'),
                    _refreshTimer: null,
                    refreshing: false,
                    async refreshToken() {
                        var t = this.token || this.getStorage().getItem('token');
                        if (!t) return false;
                        try {
                            var response = await fetch('{{ url("/api/auth/refresh") }}', {
                                method: 'POST',
                                headers: { 'Authorization': 'Bearer ' + t, 'Accept': 'application/json' }
                            });
                            var data = await response.json().catch(function() { return {}; });
                            if (response.ok && data.access_token) {
                                this.token = data.access_token;
                                if (data.user) this.user = data.user;
                                var s = this.getStorage();
                                s.setItem('token', this.token);
                                s.setItem('user', JSON.stringify(this.user));
                                this.scheduleRefresh();
                                return true;
                            }
                        } catch (e) { }
                        return false;
                    },
                    scheduleRefresh: function() {
                        var self = this;
                        if (self._refreshTimer) clearTimeout(self._refreshTimer);
                        self._refreshTimer = null;
                        var t = self.token || self.getStorage().getItem('token');
                        if (!t) return;
                        try {
                            var payload = JSON.parse(atob(t.split('.')[1]));
                            var exp = (payload.exp || 0) * 1000;
                            var now = Date.now();
                            var delay = Math.max(60000, (exp - now) - 5 * 60 * 1000);
                            if (exp - now < 10 * 60 * 1000) {
                                self.refreshToken();
                                return;
                            }
                            self._refreshTimer = setTimeout(function() { self.refreshToken(); }, delay);
                        } catch (e) { }
                    },
                    refreshUser: async function() {
                        var t = this.token || this.getStorage().getItem('token');
                        if (!t || this.refreshing) return;
                        this.refreshing = true;
                        try {
                            var response = await fetch('{{ url("/api/auth/user-profile") }}', {
                                headers: { 'Authorization': 'Bearer ' + t, 'Accept': 'application/json' }
                            });
                            if (response.ok) {
                                var updatedUser = await response.json();
                                this.user = updatedUser;
                                this.getStorage().setItem('user', JSON.stringify(updatedUser));
                                this.scheduleRefresh();
                                var path = window.location.pathname;
                                if (updatedUser.role !== 'admin' && path.startsWith('/admin')) {
                                    window.location.href = '/dashboard';
                                }
                            } else if (response.status === 401) {
                                if (!(await this.refreshToken())) this.logout();
                                else await this.refreshUser();
                            }
                        } catch (e) {
                            console.error('Failed to refresh user profile:', e);
                        } finally {
                            this.refreshing = false;
                        }
                    },
                    login: function(user, token, keepMeSignedIn) {
                        if (keepMeSignedIn === undefined) keepMeSignedIn = true;
                        localStorage.setItem('auth_storage', keepMeSignedIn ? 'local' : 'session');
                        var s = keepMeSignedIn ? localStorage : sessionStorage;
                        s.setItem('user', JSON.stringify(user));
                        s.setItem('token', token);
                        if (!keepMeSignedIn) {
                            localStorage.removeItem('user');
                            localStorage.removeItem('token');
                        }
                        this.user = user;
                        this.token = token;
                        this.scheduleRefresh();
                    },
                    logout: function() {
                        if (this._refreshTimer) clearTimeout(this._refreshTimer);
                        this._refreshTimer = null;
                        localStorage.removeItem('token');
                        localStorage.removeItem('user');
                        localStorage.removeItem('auth_storage');
                        sessionStorage.removeItem('token');
                        sessionStorage.removeItem('user');
                        this.user = {};
                        this.token = null;
                        window.location.href = '/login';
                    }
                });
            })();
            Alpine.store('alertModal', {
                show: false,
                title: 'Notice',
                message: '',
                type: 'info',
                open(message, title, type) {
                    this.message = typeof message === 'string' ? message : (message || '');
                    this.title = typeof title === 'string' ? title : 'Notice';
                    this.type = (type === 'error' || type === 'success' || type === 'info') ? type : 'info';
                    this.show = true;
                },
                close() { this.show = false; }
            });
            window.showAlert = function(msg, title, type) {
                if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('alertModal')) {
                    Alpine.store('alertModal').open(msg || '', title || 'Notice', type || 'info');
                } else {
                    window.alert(msg);
                }
            };
        });

        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                    colors: {
                        mtn: { base: '#FFCC00', dark: '#E6B800' },
                        airteltigo: { base: '#9333ea', dark: '#6b21a8' },
                        telecel: { base: '#FF0000', dark: '#CC0000' },
                        primary: @json($themePrimary),
                    }
                }
            }
        }
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        /* User app navbar */
        .nav-app {
            font-family: 'Outfit', sans-serif;
        }
        .nav-app .nav-link-app {
            position: relative;
            color: #475569;
            font-weight: 600;
            transition: color 0.2s ease;
        }
        .nav-app .nav-link-app:hover { color: #0891b2; }
        .nav-app .nav-link-app.active {
            color: #0891b2;
        }
        .nav-app .nav-link-app.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: #0891b2;
            border-radius: 1px;
        }
        .nav-app .btn-cta {
            background: #0891b2;
            color: white;
            font-weight: 600;
            border-radius: 9999px;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .nav-app .btn-cta:hover { background: #0e7490; box-shadow: 0 4px 14px rgba(8, 145, 178, 0.35); }
        .nav-app .avatar-ring {
            box-shadow: 0 0 0 2px #e2e8f0;
        }
        .nav-app .avatar-ring.open {
            box-shadow: 0 0 0 2px #0891b2;
        }
    </style>
    <style>
        .gradient-text {
            background: linear-gradient(135deg, {{ $themeGradientStart }} 0%, {{ $themeGradientEnd }} 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .mobile-nav-link {
            @apply block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50;
        }
        /* Mobile app safe area for bottom nav */
        .mobile-bottom-nav {
            padding-bottom: env(safe-area-inset-bottom, 0);
        }
    </style>
    @if(request()->is('admin*'))
    <style>
        /* Admin area: Outfit, light background, cyan accent */
        .admin-page { font-family: 'Outfit', sans-serif; }
        .admin-page .admin-back { color: #0891b2; font-weight: 600; }
        .admin-page .admin-back:hover { text-decoration: underline; }
        .admin-page .admin-card { background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .admin-page .btn-admin-primary { background: #0891b2; color: #fff; font-weight: 600; border-radius: 0.75rem; }
        .admin-page .btn-admin-primary:hover { background: #0e7490; }
        .admin-page .btn-admin-secondary { background: #fff; color: #475569; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-weight: 600; }
        .admin-page .btn-admin-secondary:hover { background: #f8fafc; }
    </style>
    @endif
    @if(request()->is('/'))
    <style>
        .nav-link-landing {
            position: relative;
            color: #8896ab;
            transition: color 0.3s ease;
        }
        .nav-link-landing::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #00d4aa;
            transition: width 0.3s ease;
        }
        .nav-link-landing:hover { color: #f0f4f8; }
        .nav-link-landing:hover::after { width: 100%; }
        .btn-primary-landing {
            background: linear-gradient(135deg, #00d4aa 0%, #00a888 100%);
            color: #0a0e17;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary-landing:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(0, 212, 170, 0.5);
        }
        .btn-secondary-landing {
            background: transparent;
            border: 1px solid #2a3548;
            color: #f0f4f8;
            transition: all 0.3s ease;
        }
        .btn-secondary-landing:hover {
            border-color: #00d4aa;
            color: #00d4aa;
            background: rgba(0, 212, 170, 0.05);
        }
    </style>
    @endif
</head>
<body class="h-full overflow-x-hidden {{ request()->is('/') ? 'text-[#f0f4f8] bg-[#0a0e17]' : 'text-slate-900' }}" x-data="{ 
    mobileMenuOpen: false, 
    drawerOpen: false,
    adminDrawerOpen: false,
    profileOpen: false,
    showLogoutModal: false,
    financeOpen: false,
    inventoryOpen: false,
    helpdeskOpen: false
}" x-init="$store.auth.refreshUser()">
    <div class="min-h-full">
        @if(request()->is('/'))
        <!-- Landing nav (sample style: dark, fixed, accent underline links) -->
        <nav class="fixed top-0 left-0 right-0 z-50 backdrop-blur-md bg-[#0a0e17]/70 border-b border-[#2a3548]" style="font-family: 'Space Grotesk', sans-serif;">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <a href="/" class="flex items-center group">
                    <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="h-14 sm:h-16 w-auto object-contain">
                </a>

                <div class="hidden md:flex items-center gap-8">
                    <a href="/#features" class="nav-link-landing font-medium">Features</a>
                    <a href="/#how-it-works" class="nav-link-landing font-medium">How it Works</a>
                    <a href="/#pricing" class="nav-link-landing font-medium">Pricing</a>
                </div>

                <div class="flex items-center gap-4">
                    <template x-if="!$store.auth.token">
                        <span class="flex items-center gap-4">
                            <a href="/login" class="btn-secondary-landing px-5 py-2.5 rounded-lg text-sm font-semibold hidden sm:inline-block">Sign In</a>
                            <a href="/signup" class="btn-primary-landing px-5 py-2.5 rounded-lg text-sm font-semibold">Get Started</a>
                        </span>
                    </template>
                    <template x-if="$store.auth.token">
                        <span class="flex items-center gap-4">
                            <a href="/dashboard" class="nav-link-landing font-medium hidden sm:block">Dashboard</a>
                            <a href="/dashboard" class="btn-primary-landing px-5 py-2.5 rounded-lg text-sm font-semibold hidden sm:inline-block">Dashboard</a>
                            <button type="button" @click="showLogoutModal = true" class="btn-secondary-landing px-5 py-2.5 rounded-lg text-sm font-semibold">Sign Out</button>
                        </span>
                    </template>
                    <button type="button" @click="drawerOpen = true" class="md:hidden p-2 text-[#8896ab] hover:text-[#f0f4f8]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </nav>
        @else
        <nav class="nav-app sticky top-0 z-50 bg-white border-b border-slate-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="/" class="flex-shrink-0 flex items-center">
                            <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="h-10 sm:h-11 w-auto object-contain">
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-1">
                        <!-- Guest Links -->
                        <template x-if="!$store.auth.token">
                            <div class="flex items-center space-x-1">
                                <a href="/bundles" class="nav-link-app {{ request()->is('bundles') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50">Bundles</a>
                                <a href="/support" class="nav-link-app {{ request()->is('support') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50">Support</a>
                                <div class="flex items-center space-x-2 ml-4 pl-4 border-l border-slate-200">
                                    <a href="/login" class="px-4 py-2 rounded-lg text-slate-600 hover:text-cyan-600 font-semibold">Login</a>
                                    <a href="/signup" class="btn-cta px-5 py-2.5 text-sm">Sign Up</a>
                                </div>
                            </div>
                        </template>

                        <!-- Dashboard Links -->
                        <template x-if="$store.auth.token">
                            <div class="flex items-center space-x-1">
                                <!-- User Links: Show if NOT on admin path -->
                                <template x-if="!window.location.pathname.startsWith('/admin')">
                                    <div class="flex items-center space-x-1">
                                        <a href="/dashboard" class="nav-link-app {{ request()->is('dashboard') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50">Dashboard</a>
                                        <a href="/bundles" class="nav-link-app {{ request()->is('bundles') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50">Buy Data</a>
                                        <a href="/orders" class="nav-link-app {{ request()->is('orders') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50">Orders</a>
                                        <a href="/topup" class="nav-link-app {{ request()->is('topup') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50">Topup</a>
                                        <a href="/reseller" class="nav-link-app {{ request()->is('reseller') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50">Reseller</a>
                                        <a href="/referral" x-show="$store.auth.user && $store.auth.user.affiliate_system_enabled" class="nav-link-app {{ request()->is('referral') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50">Referral</a>
                                        <a href="/support" class="nav-link-app {{ request()->is('support') ? 'active' : '' }} h-16 inline-flex items-center px-4 rounded-lg hover:bg-slate-50 ml-2">Support</a>
                                    </div>
                                </template>

                                <!-- Admin Links: Show ONLY if admin AND on admin path -->
                                <template x-if="$store.auth.user.role === 'admin' && window.location.pathname.startsWith('/admin')">
                                    <div class="flex items-center space-x-1">
                                        <a href="/admin/dashboard" class="{{ request()->is('admin/dashboard') ? 'text-cyan-600 border-b-2 border-cyan-600' : 'text-slate-600 hover:text-cyan-600' }} h-16 inline-flex items-center px-4 rounded-lg font-semibold">Dashboard</a>
                                        <a href="/admin/users" class="{{ request()->is('admin/users') ? 'text-cyan-600 border-b-2 border-cyan-600' : 'text-slate-600 hover:text-cyan-600' }} h-16 inline-flex items-center px-4 rounded-lg font-semibold">Users</a>
                                        
                                        <!-- Inventory Dropdown -->
                                        <div class="relative h-16 flex items-center" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                            <button class="flex items-center space-x-1 font-semibold text-slate-600 hover:text-cyan-600 transition-all outline-none px-4 rounded-lg hover:bg-slate-50 h-16 items-center">
                                                <span>Inventory</span>
                                                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="open" x-cloak class="absolute top-full left-0 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-[60]">
                                                <a href="/admin/packages" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">Packages</a>
                                                <a href="/admin/orders" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">Orders</a>
                                            </div>
                                        </div>

                                        <!-- Finance Dropdown -->
                                        <div class="relative h-16 flex items-center" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                            <button class="flex items-center space-x-1 font-semibold text-slate-600 hover:text-cyan-600 transition-all outline-none px-4 rounded-lg hover:bg-slate-50 h-16 items-center">
                                                <span>Finance</span>
                                                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="open" x-cloak class="absolute top-full left-0 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-[60]">
                                                <a href="/admin/statistics" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">Statistics</a>
                                                <a href="/admin/topups" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">Topups</a>
                                                <a href="/admin/payments" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">Payments</a>
                                                <a href="/admin/affiliates" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">Affiliates</a>
                                            </div>
                                        </div>

                                        <!-- Support Dropdown -->
                                        <div class="relative h-16 flex items-center" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                                            <button class="flex items-center space-x-1 font-semibold text-slate-600 hover:text-cyan-600 transition-all outline-none px-4 rounded-lg hover:bg-slate-50 h-16 items-center">
                                                <span>Support</span>
                                                <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="open" x-cloak class="absolute top-full left-0 w-48 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-[60]">
                                                <a href="/admin/support" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">Tickets</a>
                                                <a href="/admin/announcements" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">Alerts &amp; Announcements</a>
                                            </div>
                                        </div>

                                        <a href="/admin/announcements" class="{{ request()->is('admin/announcements') ? 'text-cyan-600 border-b-2 border-cyan-600' : 'text-slate-600 hover:text-cyan-600' }} h-16 inline-flex items-center px-4 rounded-lg font-semibold">Announcements</a>
                                        <a href="/admin/resellers" class="{{ request()->is('admin/resellers') ? 'text-cyan-600 border-b-2 border-cyan-600' : 'text-slate-600 hover:text-cyan-600' }} h-16 inline-flex items-center px-4 rounded-lg font-semibold">Resellers</a>
                                        <a href="/admin/settings" class="{{ request()->is('admin/settings') ? 'text-cyan-600 border-b-2 border-cyan-600' : 'text-slate-600 hover:text-cyan-600' }} h-16 inline-flex items-center px-4 rounded-lg font-semibold">Settings</a>
                                    </div>
                                </template>
                                
                                <div class="flex items-center space-x-2 ml-6 pl-6 border-l border-slate-200">
                                    <!-- Notification Bell -->
                                    <a href="/notifications" class="relative p-2.5 rounded-lg text-slate-500 hover:text-cyan-600 hover:bg-cyan-50 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                                    </a>

                                    <!-- Profile Dropdown -->
                                    <div class="relative">
                                        <button @click="profileOpen = !profileOpen" @click.outside="profileOpen = false" class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-slate-50 transition-all outline-none">
                                            <div class="avatar-ring rounded-full w-9 h-9 flex items-center justify-center text-sm font-bold text-white transition-shadow" :class="profileOpen ? 'open bg-cyan-600' : 'bg-cyan-500'" x-text="$store.auth.user.full_name ? $store.auth.user.full_name.charAt(0) : 'U'"></div>
                                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="profileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>

                                        <div x-show="profileOpen" 
                                             x-cloak
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-[60]">
                                            <div class="px-4 py-3 border-b border-slate-100 mb-2">
                                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Account</p>
                                                <p class="text-sm font-bold text-slate-900 truncate" x-text="$store.auth.user.full_name || 'User'"></p>
                                            </div>
                                            
                                            <a href="/profile" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">My Profile</a>
                                            <a href="/dashboard" class="block px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-cyan-600 transition-colors">User Dashboard</a>

                                            <template x-if="$store.auth.user.role === 'admin'">
                                                <a href="/admin/dashboard" class="block px-4 py-2.5 text-sm font-semibold text-cyan-600 bg-cyan-50 hover:bg-cyan-100 transition-colors border-t border-slate-100 mt-2 pt-2">Admin Console</a>
                                            </template>

                                            <div class="border-t border-slate-100 mt-2 pt-2">
                                                <button @click="profileOpen = false; showLogoutModal = true" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 transition-colors">Logout</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden flex items-center">
                        <button @click="window.location.pathname.startsWith('/admin') ? (adminDrawerOpen = true) : (drawerOpen = true)" class="p-2.5 rounded-xl text-slate-600 hover:text-cyan-600 hover:bg-slate-50 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>
        @endif

        @if(!request()->is('admin*'))
        <!-- Mobile Drawer (user pages only) -->
        <div x-show="drawerOpen" x-cloak class="md:hidden fixed inset-0 z-[90] nav-app" aria-hidden="true">
            <div x-show="drawerOpen" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="drawerOpen = false"
                 class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
            <div x-show="drawerOpen"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="fixed left-0 top-0 bottom-0 w-72 max-w-[85vw] bg-white shadow-xl z-[91] flex flex-col border-r border-slate-200">
                <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                    <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" class="h-10 w-auto object-contain">
                    <button @click="drawerOpen = false" class="p-2.5 rounded-lg text-slate-500 hover:text-cyan-600 hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto py-4">
                    <template x-if="!$store.auth.token">
                        <div class="px-3 space-y-0.5">
                            <a href="/bundles" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Bundles</a>
                            <a href="/support" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Support</a>
                            <div class="pt-4 mt-4 border-t border-slate-200 space-y-2 px-2">
                                <a href="/login" @click="drawerOpen = false" class="block px-4 py-3 rounded-xl border border-slate-200 text-center font-semibold text-slate-700">Login</a>
                                <a href="/signup" @click="drawerOpen = false" class="block px-4 py-3 rounded-xl btn-cta text-center font-semibold">Sign Up</a>
                            </div>
                        </div>
                    </template>
                    <template x-if="$store.auth.token">
                        <div class="px-3 space-y-0.5">
                            <div class="px-4 py-3 mb-2 border-b border-slate-200">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Account</p>
                                <p class="font-bold text-slate-900 truncate" x-text="$store.auth.user.full_name || 'User'"></p>
                            </div>
                            <a href="/dashboard" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Dashboard</a>
                            <a href="/bundles" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Buy Data</a>
                            <a href="/orders" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Orders</a>
                            <a href="/topup" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Top Up</a>
                            <a href="/reseller" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Reseller</a>
                            <a href="/referral" @click="drawerOpen = false" x-show="$store.auth.user && $store.auth.user.affiliate_system_enabled" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Referral</a>
                            <a href="/support" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Support</a>
                            <a href="/notifications" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Notifications</a>
                            <a href="/profile" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-semibold hover:bg-slate-50 hover:text-cyan-600">Profile</a>
                            <template x-if="$store.auth.user.role === 'admin'">
                                <a href="/admin/dashboard" @click="drawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-cyan-600 font-semibold bg-cyan-50">Admin Console</a>
                            </template>
                            <div class="pt-4 mt-4 border-t border-slate-200">
                                <button @click="drawerOpen = false; showLogoutModal = true" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 font-semibold hover:bg-red-50">Logout</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @endif

        @if(request()->is('admin*'))
        <!-- Admin Mobile Drawer -->
        <div x-show="adminDrawerOpen" x-cloak class="md:hidden fixed inset-0 z-[90]" aria-hidden="true">
            <div x-show="adminDrawerOpen" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="adminDrawerOpen = false"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div x-show="adminDrawerOpen"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full"
                 class="fixed left-0 top-0 bottom-0 w-72 max-w-[85vw] bg-white shadow-2xl z-[91] flex flex-col">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <span class="text-lg font-black text-cyan-600">Admin</span>
                    <button @click="adminDrawerOpen = false" class="p-2 -m-2 text-slate-500 hover:text-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto py-4">
                    <div class="px-4 space-y-1">
                        <a href="/admin/dashboard" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50">Dashboard</a>
                        <a href="/admin/users" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50">Users</a>
                        <p class="px-4 pt-3 pb-1 text-[10px] font-black text-slate-400 uppercase tracking-wider">Inventory</p>
                        <a href="/admin/packages" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Packages</a>
                        <a href="/admin/orders" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Orders</a>
                        <p class="px-4 pt-3 pb-1 text-[10px] font-black text-slate-400 uppercase tracking-wider">Finance</p>
                        <a href="/admin/statistics" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Statistics</a>
                        <a href="/admin/topups" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Topups</a>
                        <a href="/admin/payments" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Payments</a>
                        <a href="/admin/affiliates" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Affiliates</a>
                        <p class="px-4 pt-3 pb-1 text-[10px] font-black text-slate-400 uppercase tracking-wider">Support</p>
                        <a href="/admin/support" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Tickets</a>
                        <a href="/admin/announcements" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Announcements</a>
                        <a href="/admin/resellers" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 font-bold hover:bg-slate-50 ml-2">Resellers</a>
                        <div class="border-t border-slate-100 mt-4 pt-4">
                            <a href="/admin/settings" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-cyan-600 font-bold bg-cyan-50">Settings</a>
                            <a href="/dashboard" @click="adminDrawerOpen = false" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-600 font-bold hover:bg-slate-50 mt-1">User Dashboard</a>
                            <button @click="adminDrawerOpen = false; showLogoutModal = true" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 font-bold hover:bg-red-50 mt-1">Logout</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <main class="@if((!request()->is('admin*') && !request()->is('login') && !request()->is('signup') && !request()->is('forgot-password') && !request()->is('/')) || request()->is('admin*')) pb-24 md:pb-0 @endif">
            @yield('content')
        </main>

        @if(!request()->is('admin*') && !request()->is('login') && !request()->is('signup') && !request()->is('forgot-password') && !request()->is('/'))
        <!-- Bottom Navigation (user pages, mobile only) -->
        <nav class="nav-app md:hidden fixed bottom-0 left-0 right-0 z-[80] bg-white border-t border-slate-200 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] mobile-bottom-nav">
            <div class="flex items-stretch justify-around min-h-[56px]">
                <a href="/dashboard" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors {{ request()->is('dashboard') ? 'text-cyan-600' : '' }}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-[10px] font-semibold mt-1">Home</span>
                </a>
                <a href="/bundles" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors {{ request()->is('bundles') ? 'text-cyan-600' : '' }}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8 4-8-4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="text-[10px] font-semibold mt-1">Buy</span>
                </a>
                <a href="/orders" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors {{ request()->is('orders') ? 'text-cyan-600' : '' }}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span class="text-[10px] font-semibold mt-1">Orders</span>
                </a>
                <a href="/topup" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors {{ request()->is('topup') ? 'text-cyan-600' : '' }}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-[10px] font-semibold mt-1">Top Up</span>
                </a>
                <button type="button" @click="drawerOpen = true" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 hover:text-cyan-600 transition-colors touch-manipulation">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <span class="text-[10px] font-semibold mt-1">More</span>
                </button>
            </div>
        </nav>
        @endif

        @if(request()->is('admin*'))
        <!-- Admin Bottom Navigation (mobile only) -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-[80] bg-white/95 backdrop-blur-sm border-t border-slate-200 shadow-[0_-4px_20px_rgba(0,0,0,0.06)] mobile-bottom-nav">
            <div class="flex items-stretch justify-around min-h-[56px]">
                <a href="/admin/dashboard" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors {{ request()->is('admin/dashboard') ? 'text-cyan-600' : '' }}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span class="text-[10px] font-bold mt-1">Home</span>
                </a>
                <a href="/admin/users" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors {{ request()->is('admin/users') ? 'text-cyan-600' : '' }}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="text-[10px] font-bold mt-1">Users</span>
                </a>
                <a href="/admin/orders" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors {{ request()->is('admin/orders') ? 'text-cyan-600' : '' }}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span class="text-[10px] font-bold mt-1">Orders</span>
                </a>
                <a href="/admin/topups" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors {{ request()->is('admin/topups') ? 'text-cyan-600' : '' }}">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-[10px] font-bold mt-1">Topups</span>
                </a>
                <button type="button" @click="adminDrawerOpen = true" class="flex flex-col items-center justify-center flex-1 min-h-[56px] py-2 text-slate-500 active:bg-slate-50 transition-colors touch-manipulation">
                    <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <span class="text-[10px] font-bold mt-1">More</span>
                </button>
            </div>
        </nav>
        @endif

        <!-- Logout Confirmation Modal -->
        <div x-show="showLogoutModal" 
             x-cloak
             class="fixed inset-0 z-[200] overflow-y-auto"
             role="dialog"
             aria-modal="true">
            <!-- Backdrop -->
            <div x-show="showLogoutModal" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showLogoutModal = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-md"></div>

            <!-- Modal Content -->
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div x-show="showLogoutModal" 
                     x-transition:enter="transition ease-out duration-300 transform"
                     x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200 transform"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                     class="relative bg-white w-full max-w-md rounded-[40px] shadow-2xl overflow-hidden border border-slate-100 p-10 text-center">
                    
                    <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center text-red-500 mx-auto mb-6">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </div>

                    <h3 class="text-2xl font-black text-slate-900 mb-2">Ready to leave?</h3>
                    <p class="text-slate-500 font-medium mb-10">You're about to log out of your account. Do you wish to proceed?</p>

                    <div class="space-y-3">
                        <button @click="$store.auth.logout()" 
                                class="w-full bg-red-600 text-white font-black py-4 rounded-2xl transition-all hover:bg-red-700 active:scale-95 shadow-lg shadow-red-500/20">
                            Yes, Logout
                        </button>
                        <button @click="showLogoutModal = false" class="w-full text-slate-400 hover:text-slate-600 font-bold py-2 transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Global alert modal (replaces default alert popup) -->
            <div x-show="$store.alertModal.show"
                 x-cloak
                 class="fixed inset-0 z-[210] overflow-y-auto"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="alert-modal-title">
                <div x-show="$store.alertModal.show"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="$store.alertModal.close()"
                     class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div x-show="$store.alertModal.show"
                         @click.stop
                         x-transition:enter="transition ease-out duration-200 transform"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150 transform"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                         class="relative w-full max-w-md rounded-[32px] bg-white shadow-2xl border border-slate-200 overflow-hidden">
                        <div class="p-8 text-center"
                             :class="{
                                 'bg-primary-50 border-b border-primary-100': $store.alertModal.type === 'info',
                                 'bg-emerald-50 border-b border-emerald-100': $store.alertModal.type === 'success',
                                 'bg-red-50 border-b border-red-100': $store.alertModal.type === 'error'
                             }">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                                 :class="{
                                     'bg-primary-100 text-primary-600': $store.alertModal.type === 'info',
                                     'bg-emerald-100 text-emerald-600': $store.alertModal.type === 'success',
                                     'bg-red-100 text-red-600': $store.alertModal.type === 'error'
                                 }">
                                <template x-if="$store.alertModal.type === 'info'">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </template>
                                <template x-if="$store.alertModal.type === 'success'">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </template>
                                <template x-if="$store.alertModal.type === 'error'">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </template>
                            </div>
                            <h3 id="alert-modal-title" class="text-xl font-black text-slate-900" x-text="$store.alertModal.title"></h3>
                        </div>
                        <div class="p-8">
                            <p class="text-slate-600 font-medium whitespace-pre-wrap break-words text-left max-h-64 overflow-y-auto" x-text="$store.alertModal.message"></p>
                            <button type="button"
                                    @click="$store.alertModal.close()"
                                    class="mt-8 w-full py-4 rounded-2xl font-black transition-all active:scale-[0.98]"
                                    :class="{
                                        'bg-primary-600 text-white hover:bg-primary-700 shadow-lg shadow-primary-500/20': $store.alertModal.type === 'info',
                                        'bg-emerald-600 text-white hover:bg-emerald-700 shadow-lg shadow-emerald-500/20': $store.alertModal.type === 'success',
                                        'bg-red-600 text-white hover:bg-red-700 shadow-lg shadow-red-500/20': $store.alertModal.type === 'error'
                                    }">
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Global WhatsApp FAB – channel only (link from Admin → Settings) -->
        @php
            $whatsappChannelUrl = \App\Services\SettingsService::get('support_whatsapp_group_url') ?: '';
        @endphp
        @if($whatsappChannelUrl !== '')
        <div class="fixed bottom-24 right-4 md:bottom-8 md:right-8 z-[100]">
            <a href="{{ e($whatsappChannelUrl) }}" target="_blank" rel="noopener noreferrer"
               class="w-16 h-16 bg-[#25D366] text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-110 active:scale-95 transition-all transform hover:rotate-12 group">
                <svg class="w-8 h-8 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.414 0 0 5.414 0 12.05c0 2.123.551 4.2 1.6 6.04L0 24l6.101-1.601A11.77 11.77 0 0012.05 24.1c6.636 0 12.05-5.414 12.05-12.05 0-3.212-1.25-6.231-3.513-8.5zm-.002 0z"/></svg>
            </a>
        </div>
        @endif
    </div>
</body>
</html>
