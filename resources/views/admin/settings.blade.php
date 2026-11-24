<x-layouts.admin title="Settings">
<div class="admin-page min-h-screen bg-surface-muted py-8" x-data="adminSettingsPage()" x-init="init()">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="/admin/dashboard" class="admin-back mb-6 inline-flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Settings</p>
                <h1 class="text-3xl font-bold text-brand-navy tracking-tight">System Settings</h1>
                <p class="text-muted mt-1 text-sm">Global platform and financial configuration.</p>
            </div>
            <div class="flex items-center gap-4">
                <p x-show="saveMessage" x-text="saveMessage" :class="saveError ? 'text-red-600 font-semibold' : 'text-emerald-600 font-semibold'"></p>
                <button @click="saveSettings()" :disabled="loading" class="btn-admin-primary px-6 py-2.5 flex items-center gap-2 disabled:opacity-50">
                    <span x-text="loading ? 'Saving...' : 'Save All Changes'"></span>
                </button>
            </div>
        </div>

        <div x-show="settingsLoading" class="text-center py-12">
            <div class="animate-spin w-10 h-10 border-4 border-brand-yellow border-t-transparent rounded-full mx-auto mb-4"></div>
            <p class="text-muted font-bold">Loading settings...</p>
        </div>

        <div x-show="!settingsLoading" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Branding: Logo, Favicon, Site Name, Description -->
            <div class="admin-card p-8 border border-line/50 shadow-none md:col-span-2">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-violet-50 text-violet-600 rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343L12.657 6m6.343 1.657L21 6m-1.343 12.657L20 18M3 18l1.343-1.343M3 6l1.343 1.343"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-brand-navy">Branding</h2>
                </div>
                <p class="text-muted font-medium mb-6">Logo, favicon, site name and description are used across the entire site (header, meta tags, open graph).</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Site Name</label>
                        <input type="text" x-model="settings.site_name" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy" placeholder="R2BMarket">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Site Description</label>
                        <textarea x-model="settings.site_description" rows="3" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-bold text-brand-navy" placeholder="Short description for search engines and social sharing."></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Logo</label>
                        <p class="text-xs text-muted mb-2">Shown in header and nav. PNG, JPG, GIF, WebP or SVG. Max 2MB.</p>
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="flex-shrink-0 w-24 h-24 bg-surface-muted rounded-card border border-line flex items-center justify-center overflow-hidden">
                                <img x-show="settings.site_logo_url" :src="settings.site_logo_url" alt="Logo" class="max-w-full max-h-full object-contain">
                                <span x-show="!settings.site_logo_url" class="text-muted text-xs text-center px-2">No logo</span>
                            </div>
                            <div>
                                <input type="file" @change="logoFile = $event.target.files[0]; $event.target.value = ''" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-card file:border-0 file:font-bold file:bg-surface-muted file:text-brand-navy">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Favicon</label>
                        <p class="text-xs text-muted mb-2">Browser tab icon. PNG or ICO. Max 512KB.</p>
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-surface-muted rounded-card border border-line flex items-center justify-center overflow-hidden">
                                <img x-show="settings.site_favicon_url" :src="settings.site_favicon_url" alt="Favicon" class="max-w-full max-h-full object-contain">
                                <span x-show="!settings.site_favicon_url" class="text-muted text-xs">—</span>
                            </div>
                            <div>
                                <input type="file" @change="faviconFile = $event.target.files[0]; $event.target.value = ''" accept="image/png,image/x-icon,image/ico" class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-card file:border-0 file:font-bold file:bg-surface-muted file:text-brand-navy">
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 pt-8 border-t border-line">
                        <h3 class="text-lg font-black text-brand-navy mb-2">Theme Colors</h3>
                        <p class="text-muted font-medium mb-4 text-sm">The brand palette is fixed by the design system: navy, yellow and red. Theme overrides are retired; network badge colours stay fixed.</p>
                        <div class="hidden grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">Primary (base)</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" x-model="settings.theme_primary_hex" class="w-12 h-12 rounded-card border-2 border-line cursor-pointer shrink-0">
                                    <input type="text" x-model="settings.theme_primary_hex" placeholder="#8b5cf6" class="flex-1 px-4 py-3 rounded-card bg-surface border border-line focus:border-brand-navy outline-none font-mono text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">Gradient start</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" x-model="settings.theme_gradient_start" class="w-12 h-12 rounded-card border-2 border-line cursor-pointer shrink-0">
                                    <input type="text" x-model="settings.theme_gradient_start" placeholder="#a855f7" class="flex-1 px-4 py-3 rounded-card bg-surface border border-line focus:border-brand-navy outline-none font-mono text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">Gradient end</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" x-model="settings.theme_gradient_end" class="w-12 h-12 rounded-card border-2 border-line cursor-pointer shrink-0">
                                    <input type="text" x-model="settings.theme_gradient_end" placeholder="#6d28d9" class="flex-1 px-4 py-3 rounded-card bg-surface border border-line focus:border-brand-navy outline-none font-mono text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Platform Control -->
            <div class="admin-card p-8 border border-line/50 shadow-none">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-brand-navy">Platform Control</h2>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center justify-between p-6 rounded-card bg-surface-muted border border-line">
                        <div>
                            <p class="font-black text-brand-navy">Maintenance Mode</p>
                            <p class="text-xs font-bold text-muted mt-1 uppercase tracking-widest leading-relaxed">Turn this on to disable public access</p>
                        </div>
                        <button @click="settings.maintenance_mode = !settings.maintenance_mode" 
                                :class="settings.maintenance_mode ? 'bg-red-600' : 'bg-slate-300'"
                                class="w-14 h-8 rounded-full relative transition-all duration-300 shadow-inner">
                            <span :class="settings.maintenance_mode ? 'translate-x-7' : 'translate-x-1'"
                                  class="absolute top-1 left-0 w-6 h-6 bg-surface rounded-full shadow-none transition-transform duration-300"></span>
                        </button>
                    </div>
                    <div class="flex items-center justify-between p-6 rounded-card bg-surface-muted border border-line">
                        <div>
                            <p class="font-black text-brand-navy">Phone OTP on registration</p>
                            <p class="text-xs font-bold text-muted mt-1 uppercase tracking-widest leading-relaxed">Require SMS verification code when users sign up</p>
                        </div>
                        <button type="button" @click="settings.otp_verification_enabled = !settings.otp_verification_enabled" :class="settings.otp_verification_enabled ? 'bg-primary-600' : 'bg-slate-300'" class="relative inline-flex h-8 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            <span class="sr-only">Enable OTP verification on signup</span>
                            <span aria-hidden="true" :class="settings.otp_verification_enabled ? 'translate-x-6' : 'translate-x-1'" class="pointer-events-none inline-block h-7 w-7 transform rounded-full bg-surface shadow ring-0 transition duration-200"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- General Settings -->
            <div class="admin-card p-8 border border-line/50 shadow-none">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-brand-navy">General</h2>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Site Name</label>
                        <input type="text" x-model="settings.site_name" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                </div>
            </div>

            <!-- Finance Rules -->
            <div class="admin-card p-8 border border-line/50 shadow-none">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-green-50 text-green-600 rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-brand-navy">Finance & Fees</h2>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center justify-between p-4 rounded-card bg-surface-muted border border-line/50">
                        <div>
                            <p class="font-bold text-brand-navy">Enable affiliate system for users</p>
                            <p class="text-xs text-muted mt-1">When off, users will not see or use referral/affiliate features. Admin can still manage affiliates here.</p>
                        </div>
                        <button type="button" @click="settings.affiliate_system_enabled = !settings.affiliate_system_enabled" :class="settings.affiliate_system_enabled ? 'bg-primary-600' : 'bg-slate-300'" class="relative inline-flex h-8 w-14 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            <span class="sr-only">Enable affiliate system</span>
                            <span aria-hidden="true" :class="settings.affiliate_system_enabled ? 'translate-x-6' : 'translate-x-1'" class="pointer-events-none inline-block h-7 w-7 transform rounded-full bg-surface shadow ring-0 transition duration-200"></span>
                        </button>
                    </div>
                    <p class="text-xs text-muted mb-4">Affiliate finance: min withdrawal and fee apply to affiliate payouts; referral bonus goes to the referred user; commission is what the referrer earns when a referred user completes a purchase.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Min Withdrawal (GHS)</label>
                            <p class="text-xs text-muted mb-2">Minimum amount an affiliate can request to withdraw.</p>
                            <input type="text" x-model="settings.min_withdraw" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Txn Fee (%)</label>
                            <p class="text-xs text-muted mb-2">Deducted from affiliate withdrawal when approved (e.g. 1.5 = 1.5%).</p>
                            <input type="text" x-model="settings.txn_fee" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Referral Bonus (GHS)</label>
                        <p class="text-xs text-muted mb-2">Amount credited to the referred user (when they sign up or qualify).</p>
                        <input type="text" x-model="settings.referral_bonus" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Affiliate signup bonus (GHS)</label>
                        <p class="text-xs text-muted mb-2">Amount the referrer earns when someone signs up using their referral link.</p>
                        <input type="text" x-model="settings.affiliate_signup_bonus" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Affiliate Commission</label>
                        <p class="text-xs text-muted mb-2">Commission the referrer earns when a referred user makes a successful (delivered) purchase.</p>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="settings.affiliate_commission" class="flex-1 px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                            <span class="text-muted font-black shrink-0">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Platform Balance (GHS)</label>
                        <p class="text-xs text-muted mb-2">Used to fulfill reseller storefront orders. Top up so walking customers can buy data.</p>
                        <input type="text" x-model="settings.platform_balance" placeholder="0" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                </div>
            </div>

            <!-- Contact & Help -->
            <div class="admin-card p-8 border border-line/50 shadow-none">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-primary-50 text-primary-600 rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-brand-navy">Contact & Support</h2>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Admin WhatsApp</label>
                        <input type="text" x-model="settings.admin_whatsapp" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">WhatsApp Channel Link</label>
                        <input type="text" x-model="settings.whatsapp_channel" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Support Email</label>
                        <input type="text" x-model="settings.support_email" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                </div>
            </div>

            <!-- Email (SMTP) -->
            <div class="admin-card p-8 border border-line/50 shadow-none md:col-span-2">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-sky-50 text-sky-600 rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-brand-navy">Email (SMTP)</h2>
                </div>
                <p class="text-muted font-medium mb-6">Configure SMTP so the system can send emails (notifications, receipts, etc.). Save your settings, then use <strong>Send test email</strong> to verify.</p>
                <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-card">
                    <p class="text-sm font-bold text-amber-800 mb-1">Using Gmail?</p>
                    <p class="text-sm text-amber-900/90">Gmail does not accept your normal password for SMTP. Use an <strong>App Password</strong> instead: enable 2-Step Verification in your Google Account, then go to <strong>Security → App passwords</strong>, create one for "Mail", and paste the 16-character password here. <a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener" class="underline font-bold">Google App Password help</a></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">SMTP Host</label>
                        <input type="text" x-model="settings.smtp_host" placeholder="e.g. smtp.gmail.com" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Port</label>
                        <input type="text" x-model="settings.smtp_port" placeholder="587" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Encryption</label>
                        <select x-model="settings.smtp_encryption" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-bold text-brand-navy">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="">None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Username</label>
                        <input type="text" x-model="settings.smtp_username" placeholder="SMTP username" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy" autocomplete="off">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Password</label>
                        <input type="password" x-model="settings.smtp_password" placeholder="Leave blank to keep current" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy" autocomplete="new-password">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">From address</label>
                        <input type="email" x-model="settings.smtp_from_address" placeholder="e.g. noreply@yourdomain.com" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">From name</label>
                        <input type="text" x-model="settings.smtp_from_name" placeholder="e.g. R2BMarket" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Admin notification email</label>
                        <input type="email" x-model="settings.admin_notification_email" placeholder="e.g. business@example.com" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                        <p class="text-xs text-muted mt-1 ml-2">All admin alerts (deposits, orders, registrations, reseller requests, complaints) are sent to this address.</p>
                    </div>
                </div>
                <div class="p-6 bg-surface-muted rounded-card border border-line flex flex-col sm:flex-row sm:items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2">Send test email to</label>
                        <input type="email" x-model="testEmailTo" placeholder="Your email or any address" class="w-full px-4 py-3 rounded-card bg-surface border border-line focus:border-brand-navy outline-none font-medium text-brand-navy">
                    </div>
                    <button type="button" @click="sendTestEmail()" :disabled="testEmailSending" class="px-6 py-3 rounded-card font-black bg-sky-600 text-white hover:bg-sky-700 disabled:opacity-50 transition-all shrink-0">
                        <span x-text="testEmailSending ? 'Sending…' : 'Send test email'"></span>
                    </button>
                </div>
                <p x-show="testEmailMessage" x-text="testEmailMessage" :class="testEmailError ? 'text-red-600 font-bold mt-4' : 'text-emerald-600 font-bold mt-4'"></p>
            </div>

            <!-- Integrations (Paystack, SMS, JayBart) – API keys managed here; secrets never exposed -->
            <div class="admin-card p-8 border border-line/50 shadow-none md:col-span-2">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-brand-navy">Integrations</h2>
                </div>
                <p class="text-muted font-medium mb-6">Paystack, SMS and JayBart API keys. Stored securely; secret keys are never shown. Leave secret fields blank to keep current value.</p>
                <div class="space-y-8">
                    <div class="p-6 rounded-card bg-surface-muted border border-line">
                        <h3 class="font-black text-brand-navy mb-4">Paystack</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">Public key</label>
                                <input type="text" x-model="settings.paystack_public_key" placeholder="pk_live_… or pk_test_…" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-mono text-sm text-brand-navy" autocomplete="off">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">Secret key</label>
                                <input type="password" x-model="settings.paystack_secret_key" placeholder="Leave blank to keep current" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-mono text-sm text-brand-navy" autocomplete="new-password">
                                <p x-show="settings.paystack_secret_key_set" class="text-xs text-emerald-600 font-bold mt-2 ml-2">Currently set</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 rounded-card bg-surface-muted border border-line">
                        <h3 class="font-black text-brand-navy mb-4">SMS</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">Sender ID</label>
                                <input type="text" x-model="settings.sms_sender_id" placeholder="e.g. R2BMarket" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy" autocomplete="off">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">API key</label>
                                <input type="password" x-model="settings.sms_api_key" placeholder="Leave blank to keep current" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-mono text-sm text-brand-navy" autocomplete="new-password">
                                <p x-show="settings.sms_api_key_set" class="text-xs text-emerald-600 font-bold mt-2 ml-2">Currently set</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 rounded-card bg-surface-muted border border-line">
                        <h3 class="font-black text-brand-navy mb-4">Refer2Bundle (Data API)</h3>
                        <p class="text-muted text-sm mb-4">Used to sync bundles and place data orders. Use the base URL your provider gave you (e.g. <code class="bg-slate-200 px-1 rounded">https://databosshub.org/api/v1</code>).</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">Base URL</label>
                                <input type="url" x-model="settings.refer2bundle_base_url" placeholder="https://databosshub.org/api/v1" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-mono text-sm text-brand-navy" autocomplete="off">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-2 ml-2">API key</label>
                                <input type="password" x-model="settings.refer2bundle_api_key" placeholder="Leave blank to keep current" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-mono text-sm text-brand-navy" autocomplete="new-password">
                                <p x-show="settings.refer2bundle_api_key_set" class="text-xs text-emerald-600 font-bold mt-2 ml-2">Currently set</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Payment (Topup) -->
            <div class="admin-card p-8 border border-line/50 shadow-none md:col-span-2">
                <div class="flex items-center space-x-4 mb-8">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-card flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h2 class="text-xl font-black text-brand-navy">Manual Payment (Topup)</h2>
                </div>
                <p class="text-muted font-medium mb-6">Allow or disable manual deposit on the Top Up page. When enabled, users see the details below.</p>
                <div class="flex items-center gap-4 mb-6 p-4 bg-surface-muted rounded-card border border-line">
                    <input type="checkbox" id="manual_topup_enabled" x-model="settings.manual_topup_enabled" class="w-5 h-5 rounded border-slate-300 text-primary-600 focus:ring-primary-500">
                    <label for="manual_topup_enabled" class="font-bold text-ink">Enable manual payment (bank/MoMo) on Top Up page</label>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Deposit Number (MoMo)</label>
                        <input type="text" x-model="settings.manual_payment_number" placeholder="e.g. 0241234567" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-muted uppercase tracking-[0.2em] mb-3 ml-2">Account Name</label>
                        <input type="text" x-model="settings.manual_payment_name" placeholder="e.g. R2BMarket Official" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-black text-brand-navy">
                    </div>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="mt-12 glass p-10 rounded-[40px] border border-red-100 shadow-none bg-red-50/10">
            <h3 class="text-lg font-black text-red-600 uppercase tracking-widest mb-4">Danger Zone</h3>
            <p class="text-muted font-medium mb-8">Use these actions with extreme caution. They are irreversible.</p>
            <div class="flex flex-wrap gap-4">
                <button class="bg-surface border border-red-200 text-red-600 px-8 py-4 rounded-card font-black hover:bg-red-50 transition-all">Clear System Cache</button>
                <button class="bg-surface border border-red-200 text-red-600 px-8 py-4 rounded-card font-black hover:bg-red-50 transition-all">Reset Password Requests</button>
            </div>
        </div>
    </div>
</div>

<script>
function adminSettingsPage() {
    return {
        logoFile: null,
        faviconFile: null,
        settings: {
            site_name: 'R2BMarket',
            site_description: 'R2BMarket – Affordable mobile data bundles for MTN, AirtelTigo & Telecel in Ghana.',
            site_logo_url: '',
            site_favicon_url: '',
            theme_primary_hex: '#8b5cf6',
            theme_gradient_start: '#a855f7',
            theme_gradient_end: '#6d28d9',
            maintenance_mode: false,
            otp_verification_enabled: true,
            affiliate_system_enabled: true,
            min_withdraw: '50.00',
            txn_fee: '1.5',
            referral_bonus: '2.00',
            affiliate_signup_bonus: '1.00',
            affiliate_commission: '2.00',
            admin_whatsapp: '',
            whatsapp_channel: '',
            support_email: '',
            manual_payment_number: '0241234567',
            manual_payment_name: 'R2BMarket Official',
            manual_topup_enabled: false,
            platform_balance: '0',
            smtp_host: '',
            smtp_port: '587',
            smtp_encryption: 'tls',
            smtp_username: '',
            smtp_password: '',
            smtp_from_address: '',
            smtp_from_name: 'R2BMarket',
            admin_notification_email: 'r2bmarketservices@gmail.com',
            paystack_public_key: '',
            paystack_secret_key: '',
            paystack_secret_key_set: false,
            sms_sender_id: '',
            sms_api_key: '',
            sms_api_key_set: false,
            refer2bundle_base_url: '',
            refer2bundle_api_key: '',
            refer2bundle_api_key_set: false
        },
        testEmailTo: '',
        testEmailSending: false,
        testEmailMessage: '',
        testEmailError: false,
        loading: false,
        settingsLoading: true,
        saveMessage: '',
        saveError: false,

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchSettings();
        },

        async fetchSettings() {
            this.settingsLoading = true;
            this.saveMessage = '';
            try {
                const response = await fetch('{{ url("/api/admin/settings") }}', {
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.settings = {
                        site_name: data.site_name ?? 'R2BMarket',
                        site_description: data.site_description ?? 'R2BMarket – Affordable mobile data bundles for MTN, AirtelTigo & Telecel in Ghana.',
                        site_logo_url: data.site_logo_url ?? '',
                        site_favicon_url: data.site_favicon_url ?? '',
                        theme_primary_hex: data.theme_primary_hex ?? '#8b5cf6',
                        theme_gradient_start: data.theme_gradient_start ?? '#a855f7',
                        theme_gradient_end: data.theme_gradient_end ?? '#6d28d9',
                        maintenance_mode: data.maintenance_mode === true || data.maintenance_mode === '1',
                        otp_verification_enabled: data.otp_verification_enabled === true || data.otp_verification_enabled === '1',
                        affiliate_system_enabled: data.affiliate_system_enabled === true || data.affiliate_system_enabled === '1',
                        min_withdraw: data.min_withdraw ?? '50.00',
                        txn_fee: data.txn_fee ?? '1.5',
                        referral_bonus: data.referral_bonus ?? '2.00',
                        affiliate_signup_bonus: data.affiliate_signup_bonus ?? '1.00',
                        affiliate_commission: data.affiliate_commission ?? '2.00',
                        admin_whatsapp: data.admin_whatsapp ?? '',
                        whatsapp_channel: data.whatsapp_channel ?? '',
                        support_email: data.support_email ?? '',
                        manual_payment_number: data.manual_payment_number ?? '0241234567',
                        manual_payment_name: data.manual_payment_name ?? 'R2BMarket Official',
                        manual_topup_enabled: data.manual_topup_enabled === true || data.manual_topup_enabled === '1',
                        platform_balance: data.platform_balance ?? '0',
                        smtp_host: data.smtp_host ?? '',
                        smtp_port: data.smtp_port ?? '587',
                        smtp_encryption: data.smtp_encryption ?? 'tls',
                        smtp_username: data.smtp_username ?? '',
                        smtp_password: '',
                        smtp_from_address: data.smtp_from_address ?? '',
                        smtp_from_name: data.smtp_from_name ?? 'R2BMarket',
                        admin_notification_email: data.admin_notification_email ?? 'r2bmarketservices@gmail.com',
                        paystack_public_key: data.paystack_public_key ?? '',
                        paystack_secret_key: '',
                        paystack_secret_key_set: data.paystack_secret_key_set === true,
                        sms_sender_id: data.sms_sender_id ?? '',
                        sms_api_key: '',
                        sms_api_key_set: data.sms_api_key_set === true,
                        refer2bundle_base_url: data.refer2bundle_base_url ?? '',
                        refer2bundle_api_key: '',
                        refer2bundle_api_key_set: data.refer2bundle_api_key_set === true
                    };
                    this.testEmailTo = data.support_email || (this.$store.auth.user && this.$store.auth.user.email) || '';
                }
            } catch (e) {
                console.error('Failed to fetch settings:', e);
            } finally {
                this.settingsLoading = false;
            }
        },

        async saveSettings() {
            this.loading = true;
            this.saveMessage = '';
            this.saveError = false;
            try {
                const useFormData = this.logoFile || this.faviconFile;
                let response;
                if (useFormData) {
                    const form = new FormData();
                    form.append('site_name', this.settings.site_name);
                    form.append('site_description', this.settings.site_description || '');
                    form.append('theme_primary_hex', this.settings.theme_primary_hex || '');
                    form.append('theme_gradient_start', this.settings.theme_gradient_start || '');
                    form.append('theme_gradient_end', this.settings.theme_gradient_end || '');
                    form.append('maintenance_mode', this.settings.maintenance_mode ? '1' : '0');
                    form.append('otp_verification_enabled', this.settings.otp_verification_enabled ? '1' : '0');
                    form.append('affiliate_system_enabled', this.settings.affiliate_system_enabled ? '1' : '0');
                    form.append('min_withdraw', this.settings.min_withdraw);
                    form.append('txn_fee', this.settings.txn_fee);
                    form.append('referral_bonus', this.settings.referral_bonus);
                    form.append('affiliate_signup_bonus', this.settings.affiliate_signup_bonus);
                    form.append('affiliate_commission', this.settings.affiliate_commission);
                    form.append('admin_whatsapp', this.settings.admin_whatsapp || '');
                    form.append('whatsapp_channel', this.settings.whatsapp_channel || '');
                    form.append('support_email', this.settings.support_email || '');
                    form.append('manual_payment_number', this.settings.manual_payment_number);
                    form.append('manual_payment_name', this.settings.manual_payment_name);
                    form.append('manual_topup_enabled', this.settings.manual_topup_enabled ? '1' : '0');
                    form.append('platform_balance', this.settings.platform_balance);
                    form.append('smtp_host', this.settings.smtp_host || '');
                    form.append('smtp_port', this.settings.smtp_port);
                    form.append('smtp_encryption', this.settings.smtp_encryption || '');
                    form.append('smtp_username', this.settings.smtp_username || '');
                    if (this.settings.smtp_password) form.append('smtp_password', this.settings.smtp_password);
                    form.append('smtp_from_address', this.settings.smtp_from_address || '');
                    form.append('smtp_from_name', this.settings.smtp_from_name || '');
                    form.append('admin_notification_email', this.settings.admin_notification_email || '');
                    form.append('paystack_public_key', this.settings.paystack_public_key || '');
                    if (this.settings.paystack_secret_key) form.append('paystack_secret_key', this.settings.paystack_secret_key);
                    form.append('sms_sender_id', this.settings.sms_sender_id || '');
                    if (this.settings.sms_api_key) form.append('sms_api_key', this.settings.sms_api_key);
                    form.append('refer2bundle_base_url', this.settings.refer2bundle_base_url || '');
                    if (this.settings.refer2bundle_api_key) form.append('refer2bundle_api_key', this.settings.refer2bundle_api_key);
                    if (this.logoFile) form.append('logo', this.logoFile);
                    if (this.faviconFile) form.append('favicon', this.faviconFile);
                    response = await fetch('{{ url("/api/admin/settings") }}', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + this.$store.auth.token,
                            'Accept': 'application/json'
                        },
                        body: form
                    });
                    this.logoFile = null;
                    this.faviconFile = null;
                } else {
                    response = await fetch('{{ url("/api/admin/settings") }}', {
                        method: 'PUT',
                        headers: {
                            'Authorization': 'Bearer ' + this.$store.auth.token,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            site_name: this.settings.site_name,
                            site_description: this.settings.site_description,
                            theme_primary_hex: this.settings.theme_primary_hex,
                            theme_gradient_start: this.settings.theme_gradient_start,
                            theme_gradient_end: this.settings.theme_gradient_end,
                            maintenance_mode: this.settings.maintenance_mode,
                            otp_verification_enabled: this.settings.otp_verification_enabled,
                            affiliate_system_enabled: this.settings.affiliate_system_enabled,
                            min_withdraw: this.settings.min_withdraw,
                            txn_fee: this.settings.txn_fee,
                            referral_bonus: this.settings.referral_bonus,
                            affiliate_signup_bonus: this.settings.affiliate_signup_bonus,
                            affiliate_commission: this.settings.affiliate_commission,
                            admin_whatsapp: this.settings.admin_whatsapp,
                            whatsapp_channel: this.settings.whatsapp_channel,
                            support_email: this.settings.support_email,
                            manual_payment_number: this.settings.manual_payment_number,
                            manual_payment_name: this.settings.manual_payment_name,
                            manual_topup_enabled: this.settings.manual_topup_enabled,
                            platform_balance: this.settings.platform_balance,
                            smtp_host: this.settings.smtp_host,
                            smtp_port: this.settings.smtp_port,
                            smtp_encryption: this.settings.smtp_encryption,
                            smtp_username: this.settings.smtp_username,
                            smtp_password: this.settings.smtp_password || undefined,
                            smtp_from_address: this.settings.smtp_from_address,
                            smtp_from_name: this.settings.smtp_from_name,
                            admin_notification_email: this.settings.admin_notification_email,
                            paystack_public_key: this.settings.paystack_public_key || undefined,
                            paystack_secret_key: this.settings.paystack_secret_key || undefined,
                            sms_sender_id: this.settings.sms_sender_id || undefined,
                            sms_api_key: this.settings.sms_api_key || undefined,
                            refer2bundle_base_url: this.settings.refer2bundle_base_url || undefined,
                            refer2bundle_api_key: this.settings.refer2bundle_api_key || undefined
                        })
                    });
                }
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    this.saveMessage = data.message || 'Settings updated successfully.';
                    if (data.settings) this.settings = { ...this.settings, ...data.settings };
                    this.settings.paystack_secret_key = '';
                    this.settings.sms_api_key = '';
                    this.settings.refer2bundle_api_key = '';
                    setTimeout(() => { this.saveMessage = ''; }, 4000);
                } else {
                    this.saveError = true;
                    this.saveMessage = (data.errors && Object.values(data.errors).flat().join(' ')) || data.message || 'Failed to save settings.';
                }
            } catch (e) {
                console.error(e);
                this.saveError = true;
                this.saveMessage = 'Network error. Try again.';
            } finally {
                this.loading = false;
            }
        },

        async sendTestEmail() {
            this.testEmailMessage = '';
            this.testEmailError = false;
            const to = (this.testEmailTo || '').trim();
            if (!to) {
                this.testEmailMessage = 'Enter an email address to send the test to.';
                this.testEmailError = true;
                return;
            }
            this.testEmailSending = true;
            try {
                const body = {
                    to: to,
                    smtp_host: this.settings.smtp_host,
                    smtp_port: this.settings.smtp_port,
                    smtp_encryption: this.settings.smtp_encryption,
                    smtp_username: this.settings.smtp_username,
                    smtp_password: this.settings.smtp_password,
                    smtp_from_address: this.settings.smtp_from_address,
                    smtp_from_name: this.settings.smtp_from_name
                };
                const response = await fetch('{{ url("/api/admin/settings/test-email") }}', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    this.testEmailMessage = data.message || 'Test email sent.';
                    setTimeout(() => { this.testEmailMessage = ''; }, 5000);
                } else {
                    this.testEmailError = true;
                    this.testEmailMessage = data.error || 'Failed to send test email.';
                }
            } catch (e) {
                this.testEmailError = true;
                this.testEmailMessage = 'Network error. Try again.';
            } finally {
                this.testEmailSending = false;
            }
        }
    };
}
</script>
</x-layouts.admin>
