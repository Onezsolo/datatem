<x-layouts.member title="Profile">
<style>
.profile-page { font-family: inherit; }
</style>
<div class="profile-page" x-data="profilePage()" x-init="init()">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Profile</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Profile &amp; settings</h1>
                <p class="mt-1 text-sm text-muted">Manage your personal details, delivery information and account security.</p>
            </div>
            <a href="{{ url('/dashboard') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Back to dashboard</a>
        </header>

        <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
            <!-- Sidebar -->
            <div class="lg:w-72 flex-shrink-0">
                <div class="bg-surface rounded-card p-6 shadow-none border border-line">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-brand-navy text-white rounded-card flex items-center justify-center text-lg font-bold" x-text="user.full_name ? user.full_name.charAt(0) : 'U'"></div>
                        <div class="min-w-0">
                            <h2 class="font-bold text-brand-navy truncate" x-text="user.full_name"></h2>
                            <p class="text-xs font-semibold text-muted uppercase tracking-wider">Active Account</p>
                        </div>
                    </div>

                    <div class="mb-6 rounded-control border border-line bg-surface-muted p-4">
                        <p class="text-[0.625rem] font-bold uppercase tracking-[0.14em] text-muted">Wallet balance</p>
                        <p class="mt-1 text-lg font-extrabold text-brand-navy">GHS <span x-text="Number(user.balance || 0).toFixed(2)"></span></p>
                        <a href="{{ url('/topup') }}" class="mt-1 inline-block text-xs font-bold text-brand-navy underline underline-offset-4">Top up wallet</a>
                    </div>
                    <nav class="space-y-1">
                        <button @click="activeSection = 'personal'" :class="activeSection === 'personal' ? 'bg-brand-navy text-white' : 'text-muted hover:bg-surface-muted hover:text-brand-navy'" class="w-full flex items-center gap-3 px-4 py-3 rounded-control font-semibold transition-all text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Personal Info</span>
                        </button>
                        <button @click="activeSection = 'security'" :class="activeSection === 'security' ? 'bg-brand-navy text-white' : 'text-muted hover:bg-surface-muted hover:text-brand-navy'" class="w-full flex items-center gap-3 px-4 py-3 rounded-control font-semibold transition-all text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Security</span>
                        </button>
                    </nav>


                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div x-show="successMessage" x-transition class="mb-6 p-4 bg-brand-yellow/20 border border-brand-yellow text-brand-navy rounded-card font-semibold text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="successMessage"></span>
                </div>

                <!-- Personal Info -->
                <div x-show="activeSection === 'personal'" x-transition class="bg-surface rounded-card p-6 md:p-8 shadow-none border border-line">
                    <h2 class="text-lg font-bold text-brand-navy mb-1">Personal Information</h2>
                    <p class="text-muted text-sm mb-6">Update your name and contact details.</p>

                    <form @submit.prevent="saveProfile" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Full Name</label>
                                <input type="text" x-model="user.full_name" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Email</label>
                                <input type="email" x-model="user.email" disabled class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line text-muted cursor-not-allowed font-medium text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Phone</label>
                                <input type="tel" x-model="user.phone" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Account status</label>
                                <div class="flex items-center justify-between rounded-control border border-line bg-surface-muted px-4 py-3 text-sm font-semibold text-brand-navy">
                                    <span class="inline-flex items-center gap-2"><span class="inline-block h-2 w-2 rounded-control bg-brand-yellow" aria-hidden="true"></span> Active</span>
                                    <a href="{{ url('/topup') }}" class="text-xs font-bold underline underline-offset-4">Wallet GHS <span x-text="Number(user.balance || 0).toFixed(2)"></span></a>
                                </div>
                            </div>
                        </div>
                        <div class="pt-5 border-t border-line flex justify-end">
                            <button type="submit" class="px-6 py-3 rounded-control font-semibold bg-brand-navy text-white hover:bg-brand-navy transition-colors text-sm">Save changes</button>
                        </div>
                    </form>

                    <div class="mt-8 border-t border-line pt-6">
                        <h3 class="text-base font-bold text-brand-navy">Delivery details</h3>
                        <p class="mt-1 text-sm text-muted">Saved for shop checkout so delivery fields are filled next time.</p>

                        <form @submit.prevent="saveDeliveryDetails" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Delivery name</label>
                                <input type="text" name="delivery_name" x-model="user.delivery_name" placeholder="Receiver name" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Delivery phone</label>
                                <input type="tel" name="delivery_phone" x-model="user.delivery_phone" placeholder="0244000000" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Delivery address</label>
                                <input type="text" name="delivery_address" x-model="user.delivery_address" placeholder="Street, house number, landmark" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">City or town</label>
                                <input type="text" name="delivery_city" x-model="user.delivery_city" placeholder="Accra" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Region</label>
                                <input type="text" name="delivery_region" x-model="user.delivery_region" placeholder="Greater Accra" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Delivery zone</label>
                                <select name="delivery_zone" x-model="user.delivery_zone" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                                    <option value="">Use checkout default</option>
                                    @foreach ($deliveryZones as $key => $zone)
                                        <option value="{{ $key }}">{{ $zone['label'] }} - {{ config('store.currency') }} {{ number_format((float) $zone['fee'], 2) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Delivery notes</label>
                                <input type="text" name="delivery_notes" x-model="user.delivery_notes" placeholder="Optional instructions" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div class="md:col-span-2 flex justify-end">
                                <button type="submit" :disabled="loading" class="px-6 py-3 rounded-control font-semibold bg-brand-yellow text-brand-navy hover:bg-brand-yellow-strong disabled:opacity-50 transition-colors text-sm">Save delivery details</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security -->
                <div x-show="activeSection === 'security'" x-transition class="bg-surface rounded-card p-6 md:p-8 shadow-none border border-line">
                    <h2 class="text-lg font-bold text-brand-navy mb-1">Change Password</h2>
                    <p class="text-muted text-sm mb-6">Update your password to keep your account secure.</p>

                    <form @submit.prevent="changePassword" class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Current Password</label>
                            <input type="password" x-model="security.current_password" placeholder="••••••••" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">New Password</label>
                            <input type="password" x-model="security.password" placeholder="Min. 6 characters" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-sm" required minlength="6">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Confirm New Password</label>
                            <input type="password" x-model="security.password_confirmation" placeholder="Confirm new password" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-sm" required minlength="6">
                        </div>
                        <p x-show="securityError" x-text="securityError" class="text-brand-red-strong font-semibold text-sm"></p>
                        <div class="pt-5 flex justify-end">
                            <button type="submit" :disabled="securityLoading" class="px-6 py-3 rounded-control font-semibold bg-brand-navy text-white hover:bg-brand-navy disabled:opacity-50 transition-colors text-sm">Update password</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

        <section class="rounded-card border border-line bg-surface p-6 md:p-8" aria-labelledby="danger-zone-heading">
            <h2 id="danger-zone-heading" class="text-lg font-bold text-brand-red-strong">Danger zone</h2>
            <p class="mt-1 text-sm text-muted">Irreversible actions. Proceed with caution.</p>

            <div class="mt-4 rounded-card border border-line bg-surface-muted p-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface text-brand-red-strong">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-bold text-brand-navy">Delete your account</h3>
                        <p class="mt-1 text-sm text-muted">Once deleted, your data, transactions, balance and referral history are permanently removed.</p>
                        <button @click="showDeleteModal = true" class="mt-4 rounded-control bg-brand-red-strong px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-red-strong-hover">Permanently delete account</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Delete account modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[200] overflow-y-auto" role="dialog" aria-modal="true">
        <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" @click="showDeleteModal = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200 transform" @click.stop class="relative bg-surface w-full max-w-md rounded-card shadow-none border border-line p-6 md:p-8 text-center">
                <div class="w-14 h-14 bg-surface-muted rounded-card flex items-center justify-center text-brand-red-strong mx-auto mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-brand-navy mb-2">Confirm account deletion</h3>
                <p class="text-muted text-sm mb-4">This cannot be undone. Enter your password to confirm.</p>
                <input type="password" x-model="deletePassword" placeholder="Your current password" class="w-full px-4 py-3 rounded-control bg-surface-muted border border-line focus:outline-none focus:border-red-500 font-medium text-sm mb-3" @keydown.enter.prevent="deleteAccount()">
                <p x-show="deleteError" x-text="deleteError" class="text-brand-red-strong font-semibold text-sm mb-4"></p>
                <div class="space-y-2">
                    <button @click="deleteAccount" :disabled="deleteLoading" class="w-full py-3 rounded-control font-semibold bg-brand-red-strong text-white hover:bg-brand-red-strong-hover disabled:opacity-50 transition-colors text-sm">Confirm & delete</button>
                    <button @click="showDeleteModal = false; deletePassword = ''; deleteError = ''" :disabled="deleteLoading" class="w-full py-2 text-muted hover:text-ink font-medium text-sm">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function profilePage() {
    return {
        activeSection: 'personal',
        user: { full_name: 'User', email: '', phone: '', balance: 0, delivery_name: '', delivery_phone: '', delivery_address: '', delivery_city: '', delivery_region: '', delivery_zone: '', delivery_notes: '' },
        successMessage: '',
        showDeleteModal: false,
        deletePassword: '',
        deleteError: '',
        deleteLoading: false,
        loading: false,
        security: { current_password: '', password: '', password_confirmation: '' },
        securityError: '',
        securityLoading: false,

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            try {
                const stored = localStorage.getItem('user');
                if (stored) this.user = Object.assign({}, this.user, JSON.parse(stored));
            } catch (e) {}
            await this.fetchUser();
        },

        async fetchUser() {
            try {
                const response = await fetch('{{ url("/api/auth/user-profile") }}', {
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.user = Object.assign({}, this.user, {
                        full_name: data.full_name ?? this.user.full_name,
                        email: data.email ?? this.user.email,
                        phone: data.phone ?? this.user.phone,
                        balance: data.balance ?? this.user.balance,
                        delivery_name: data.delivery_name ?? this.user.delivery_name,
                        delivery_phone: data.delivery_phone ?? this.user.delivery_phone,
                        delivery_address: data.delivery_address ?? this.user.delivery_address,
                        delivery_city: data.delivery_city ?? this.user.delivery_city,
                        delivery_region: data.delivery_region ?? this.user.delivery_region,
                        delivery_zone: data.delivery_zone ?? this.user.delivery_zone,
                        delivery_notes: data.delivery_notes ?? this.user.delivery_notes
                    });
                    if (this.$store.auth.user && typeof this.$store.auth.user === 'object') {
                        this.$store.auth.user = Object.assign({}, this.$store.auth.user, data);
                    }
                    localStorage.setItem('user', JSON.stringify(this.user));
                }
            } catch (e) {
                console.error('Failed to load profile:', e);
            }
        },

        async saveProfile() {
            if (this.activeSection !== 'personal') return;
            this.loading = true;
            this.successMessage = '';
            try {
                const response = await fetch('{{ url("/api/auth/user/profile") }}', {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        full_name: this.user.full_name,
                        phone: this.user.phone || ''
                    })
                });
                const data = await response.json().catch(function() { return {}; });
                if (response.ok) {
                    this.successMessage = data.message || 'Profile updated successfully!';
                    if (data.user) {
                        this.user.full_name = data.user.full_name;
                        this.user.email = data.user.email;
                        this.user.phone = data.user.phone || '';
                        localStorage.setItem('user', JSON.stringify(this.user));
                        if (this.$store.auth.user) this.$store.auth.user = Object.assign({}, this.$store.auth.user, data.user);
                    }
                    var self = this;
                    setTimeout(function () { self.successMessage = ''; }, 3000);
                } else {
                    var err = (data.errors && (data.errors.full_name && data.errors.full_name[0]) || (data.errors.phone && data.errors.phone[0])) || data.message || 'Failed to update profile.';
                    alert(err);
                }
            } catch (e) {
                console.error(e);
                alert('Network error. Please try again.');
            } finally {
                this.loading = false;
            }
        },


        async saveDeliveryDetails() {
            this.loading = true;
            this.successMessage = '';
            try {
                const response = await fetch('{{ url("/api/auth/user/delivery-details") }}', {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        delivery_name: this.user.delivery_name || null,
                        delivery_phone: this.user.delivery_phone || null,
                        delivery_address: this.user.delivery_address || null,
                        delivery_city: this.user.delivery_city || null,
                        delivery_region: this.user.delivery_region || null,
                        delivery_zone: this.user.delivery_zone || null,
                        delivery_notes: this.user.delivery_notes || null
                    })
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    this.user = Object.assign({}, this.user, data.user || {});
                    if (this.$store.auth.user) {
                        this.$store.auth.user = Object.assign({}, this.$store.auth.user, data.user || {});
                    }
                    this.successMessage = 'Delivery details saved.';
                    setTimeout(() => { this.successMessage = ''; }, 3000);
                } else {
                    window.showAlert(data.message || 'Could not save delivery details.');
                }
            } catch (error) {
                window.showAlert('Could not save delivery details. Check your connection.');
            } finally {
                this.loading = false;
            }
        },

        async changePassword() {
            this.securityError = '';
            if (!this.security.current_password || !this.security.password || !this.security.password_confirmation) {
                this.securityError = 'Please fill all password fields.';
                return;
            }
            if (this.security.password !== this.security.password_confirmation) {
                this.securityError = 'New password and confirmation do not match.';
                return;
            }
            if (this.security.password.length < 6) {
                this.securityError = 'New password must be at least 6 characters.';
                return;
            }
            this.securityLoading = true;
            try {
                const response = await fetch('{{ url("/api/auth/user/password") }}', {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        current_password: this.security.current_password,
                        password: this.security.password,
                        password_confirmation: this.security.password_confirmation
                    })
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    this.successMessage = data.message || 'Password updated successfully.';
                    this.security = { current_password: '', password: '', password_confirmation: '' };
                    var self = this;
                    setTimeout(function () { self.successMessage = ''; }, 4000);
                } else {
                    this.securityError = (data.errors && (data.errors.current_password && data.errors.current_password[0]) || (data.errors.password && data.errors.password[0])) || data.error || data.message || 'Failed to update password.';
                }
            } catch (e) {
                this.securityError = 'Network error. Please try again.';
            } finally {
                this.securityLoading = false;
            }
        },

        async deleteAccount() {
            if (!this.deletePassword) {
                this.deleteError = 'Please enter your password to confirm.';
                return;
            }
            this.deleteError = '';
            this.deleteLoading = true;
            try {
                const response = await fetch('{{ url("/api/auth/user/account") }}', {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ password: this.deletePassword })
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    this.showDeleteModal = false;
                    this.deletePassword = '';
                    var st = (localStorage.getItem('auth_storage') === 'session') ? sessionStorage : localStorage;
                    st.removeItem('token');
                    st.removeItem('user');
                    window.location.href = '/login?deleted=1';
                } else {
                    this.deleteError = data.error || data.message || 'Failed to delete account.';
                }
            } catch (e) {
                this.deleteError = 'Network error. Please try again.';
            } finally {
                this.deleteLoading = false;
            }
        }
    };
}
</script>
</x-layouts.member>
