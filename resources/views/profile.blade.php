<x-layouts.member title="Profile">
<style>
.profile-page { font-family: inherit; }
</style>
<div class="profile-page min-h-screen bg-surface-muted py-8 md:py-12" x-data="profilePage()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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

                    <nav class="space-y-1">
                        <button @click="activeSection = 'personal'" :class="activeSection === 'personal' ? 'bg-surface-muted text-brand-navy' : 'text-muted hover:bg-surface-muted'" class="w-full flex items-center gap-3 px-4 py-3 rounded-card font-semibold transition-all text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <span>Personal Info</span>
                        </button>
                        <button @click="activeSection = 'security'" :class="activeSection === 'security' ? 'bg-surface-muted text-brand-navy' : 'text-muted hover:bg-surface-muted'" class="w-full flex items-center gap-3 px-4 py-3 rounded-card font-semibold transition-all text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Security</span>
                        </button>
                        <button @click="activeSection = 'danger'" :class="activeSection === 'danger' ? 'bg-red-50 text-red-600' : 'text-muted hover:bg-red-50/50 hover:text-red-500'" class="w-full flex items-center gap-3 px-4 py-3 rounded-card font-semibold transition-all text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Danger Zone</span>
                        </button>
                    </nav>

                    <div class="mt-6 pt-6 border-t border-line">
                        <button @click="localStorage.removeItem('token'); localStorage.removeItem('user'); window.location.href='/login'" class="w-full flex items-center gap-3 px-4 py-3 rounded-card font-semibold text-muted hover:text-red-600 hover:bg-red-50 transition-all text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                            <span>Sign Out</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
                <div x-show="successMessage" x-transition class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-card font-semibold text-sm flex items-center gap-2">
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
                                <input type="text" x-model="user.full_name" class="w-full px-4 py-3 rounded-card bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Email</label>
                                <input type="email" x-model="user.email" disabled class="w-full px-4 py-3 rounded-card bg-surface-muted border border-line text-muted cursor-not-allowed font-medium text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Phone</label>
                                <input type="tel" x-model="user.phone" class="w-full px-4 py-3 rounded-card bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-brand-navy text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Account Tier</label>
                                <div class="px-4 py-3 rounded-card bg-surface-muted border border-cyan-200 font-semibold text-brand-navy-soft flex items-center justify-between text-sm">
                                    <span>Premium User</span>
                                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a.75.75 0 01.742-.147 12.35 12.35 0 003.991 0 .75.75 0 01.742.147l4.5 4.5a.75.75 0 01.072 1.012l-6 8a.75.75 0 01-1.2 0l-6-8a.75.75 0 01.072-1.012l4.5-4.5z" clip-rule="evenodd"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="pt-5 border-t border-line flex justify-end">
                            <button type="submit" class="px-6 py-3 rounded-card font-semibold bg-brand-navy text-white hover:bg-brand-navy transition-colors text-sm">Save changes</button>
                        </div>
                    </form>
                </div>

                <!-- Security -->
                <div x-show="activeSection === 'security'" x-transition class="bg-surface rounded-card p-6 md:p-8 shadow-none border border-line">
                    <h2 class="text-lg font-bold text-brand-navy mb-1">Change Password</h2>
                    <p class="text-muted text-sm mb-6">Update your password to keep your account secure.</p>

                    <form @submit.prevent="changePassword" class="space-y-5">
                        <div>
                            <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Current Password</label>
                            <input type="password" x-model="security.current_password" placeholder="••••••••" class="w-full px-4 py-3 rounded-card bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">New Password</label>
                            <input type="password" x-model="security.password" placeholder="Min. 6 characters" class="w-full px-4 py-3 rounded-card bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-sm" required minlength="6">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Confirm New Password</label>
                            <input type="password" x-model="security.password_confirmation" placeholder="Confirm new password" class="w-full px-4 py-3 rounded-card bg-surface-muted border border-line focus:outline-none focus:border-brand-navy font-medium text-sm" required minlength="6">
                        </div>
                        <p x-show="securityError" x-text="securityError" class="text-red-600 font-semibold text-sm"></p>
                        <div class="pt-5 flex justify-end">
                            <button type="submit" :disabled="securityLoading" class="px-6 py-3 rounded-card font-semibold bg-brand-navy text-white hover:bg-brand-navy disabled:opacity-50 transition-colors text-sm">Update password</button>
                        </div>
                    </form>
                </div>

                <!-- Danger Zone -->
                <div x-show="activeSection === 'danger'" x-transition class="bg-surface rounded-card p-6 md:p-8 shadow-none border border-red-100">
                    <h2 class="text-lg font-bold text-red-600 mb-1">Danger Zone</h2>
                    <p class="text-muted text-sm mb-6">Irreversible actions. Proceed with caution.</p>

                    <div class="p-6 rounded-card bg-red-50 border border-red-100">
                        <div class="flex items-start gap-4 mb-6">
                            <div class="w-10 h-10 bg-surface rounded-card flex items-center justify-center text-red-600 shrink-0 shadow-none">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-brand-navy">Delete Your Account</h3>
                                <p class="text-muted text-sm mt-1">Once deleted, all your data—transactions, balance, referrals—will be permanently removed.</p>
                            </div>
                        </div>
                        <button @click="showDeleteModal = true" class="px-6 py-3 rounded-card font-semibold bg-red-600 text-white hover:bg-red-700 transition-colors text-sm">Permanently delete account</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete account modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-[200] overflow-y-auto" role="dialog" aria-modal="true">
        <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" @click="showDeleteModal = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200 transform" @click.stop class="relative bg-surface w-full max-w-md rounded-card shadow-none border border-line p-6 md:p-8 text-center">
                <div class="w-14 h-14 bg-red-100 rounded-card flex items-center justify-center text-red-600 mx-auto mb-4">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-brand-navy mb-2">Confirm account deletion</h3>
                <p class="text-muted text-sm mb-4">This cannot be undone. Enter your password to confirm.</p>
                <input type="password" x-model="deletePassword" placeholder="Your current password" class="w-full px-4 py-3 rounded-card bg-surface-muted border border-line focus:outline-none focus:border-red-500 font-medium text-sm mb-3" @keydown.enter.prevent="deleteAccount()">
                <p x-show="deleteError" x-text="deleteError" class="text-red-600 font-semibold text-sm mb-4"></p>
                <div class="space-y-2">
                    <button @click="deleteAccount" :disabled="deleteLoading" class="w-full py-3 rounded-card font-semibold bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 transition-colors text-sm">Confirm & delete</button>
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
        user: { full_name: 'User', email: '', phone: '' },
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
                    this.user = {
                        full_name: data.full_name ?? this.user.full_name,
                        email: data.email ?? this.user.email,
                        phone: data.phone ?? this.user.phone
                    };
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
