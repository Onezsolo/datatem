<x-layouts.admin title="Announcements">
<div class="admin-page min-h-screen bg-surface-muted py-8" x-data="adminAnnouncementsPage()" x-init="init()">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="/admin/dashboard" class="admin-back mb-4 inline-flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7 7-7-7"/></svg>
            Back to Dashboard
        </a>
        <p class="text-muted text-sm font-medium mb-8">
            <a href="/admin/notifications" class="admin-back font-semibold">View my notifications</a> (orders, topups, complaints)
        </p>

        <!-- Announcements / System Alerts -->
        <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Support</p>
        <h1 class="text-3xl font-bold text-brand-navy tracking-tight">Announcements</h1>
        <p class="text-muted mt-1 text-sm mb-8">Broadcast updates via in-app, email, and/or SMS.</p>

        <div class="admin-card p-8">
            <form @submit.prevent="send" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-ink mb-2">Alert Title</label>
                    <input type="text" x-model="title" required placeholder="e.g. System Maintenance" class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-medium text-brand-navy">
                </div>
                <div>
                    <label class="block text-sm font-bold text-ink mb-2">Message Body</label>
                    <textarea x-model="message" required rows="4" placeholder="Type your broadcast message here..." class="w-full px-6 py-4 rounded-card bg-surface border border-line focus:border-brand-navy outline-none transition-all font-medium text-brand-navy"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-ink mb-2">Channels</label>
                    <p class="text-xs text-muted mb-2">Choose how to deliver: in-app notification, email, and/or SMS.</p>
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="channels.in_app" class="rounded border-slate-300 text-brand-navy focus:ring-brand-navy">
                            <span class="font-medium text-ink">In-app</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="channels.email" class="rounded border-slate-300 text-brand-navy focus:ring-brand-navy">
                            <span class="font-medium text-ink">Email</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="channels.sms" class="rounded border-slate-300 text-brand-navy focus:ring-brand-navy">
                            <span class="font-medium text-ink">SMS</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-ink mb-2">Send to</label>
                    <div class="flex flex-wrap gap-4 mb-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="audience" value="all" class="text-brand-navy focus:ring-brand-navy">
                            <span class="font-medium text-ink">All users</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="audience" value="resellers" class="text-brand-navy focus:ring-brand-navy">
                            <span class="font-medium text-ink">Resellers only</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="audience" value="users" class="text-brand-navy focus:ring-brand-navy">
                            <span class="font-medium text-ink">Selected users</span>
                        </label>
                    </div>
                    <div x-show="audience === 'users'" class="mt-2">
                        <select x-model="userIds" multiple class="w-full px-4 py-3 rounded-card border border-line focus:border-brand-navy outline-none font-medium text-brand-navy" style="min-height: 120px;">
                            <template x-for="u in recipientList" :key="u.id">
                                <option :value="u.id" x-text="u.full_name + ' (' + (u.email || u.phone || '') + ')'"></option>
                            </template>
                        </select>
                        <p class="text-xs text-muted mt-1">Hold Ctrl/Cmd to select multiple.</p>
                    </div>
                </div>
                <div x-show="sendError" class="p-3 rounded-card bg-red-50 border border-red-200 text-red-700 text-sm" x-text="sendError"></div>
                <div x-show="sendSuccess" class="p-3 rounded-card bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm" x-text="sendSuccess"></div>
                <div class="pt-6">
                    <button type="submit" :disabled="loading" class="w-full btn-admin-primary py-4 rounded-card flex items-center justify-center gap-2 disabled:opacity-70">
                        <span x-text="loading ? 'Sending...' : 'Send Announcement'"></span>
                        <svg x-show="!loading" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167H3.38a1.345 1.345 0 01-1.35-1.108l-.51-4.139a1.345 1.345 0 011.057-1.479l7.332-1.294a1.76 1.76 0 011.883 1.536zm0 0l6.27-6.27m0 0a2.09 2.09 0 112.956 2.956L14 8.27m6.27-6.27L20 8.27M13 19.27h.01"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function adminAnnouncementsPage() {
    return {
        title: '',
        message: '',
        channels: { in_app: true, email: false, sms: false },
        audience: 'all',
        userIds: [],
        recipientList: [],
        sendError: '',
        sendSuccess: '',
        loading: false,

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchRecipients();
        },

        async fetchRecipients() {
            try {
                const response = await fetch('{{ url("/api/admin/announcements/recipients") }}', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.recipientList = Array.isArray(data) ? data : (data.data || []);
                }
            } catch (e) {
                console.error('Failed to fetch recipients:', e);
            }
        },

        async send() {
            const ch = [];
            if (this.channels.in_app) ch.push('in_app');
            if (this.channels.email) ch.push('email');
            if (this.channels.sms) ch.push('sms');
            if (ch.length === 0) {
                this.sendError = 'Select at least one channel (In-app, Email, or SMS).';
                this.sendSuccess = '';
                return;
            }
            if (this.audience === 'users' && (!this.userIds || this.userIds.length === 0)) {
                this.sendError = 'Select at least one user when sending to selected users.';
                this.sendSuccess = '';
                return;
            }
            this.sendError = '';
            this.sendSuccess = '';
            this.loading = true;
            try {
                const body = {
                    title: this.title,
                    message: this.message,
                    channels: ch,
                    audience: this.audience
                };
                if (this.audience === 'users') {
                    body.user_ids = Array.isArray(this.userIds) ? this.userIds : [this.userIds];
                }
                const response = await fetch('{{ url("/api/admin/announcements") }}', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    this.sendSuccess = 'Sent: ' + (data.in_app_count || 0) + ' in-app, ' + (data.email_count || 0) + ' email(s), ' + (data.sms_count || 0) + ' SMS.';
                    this.title = '';
                    this.message = '';
                } else {
                    this.sendError = data.error || data.message || 'Failed to send announcement.';
                }
            } catch (e) {
                this.sendError = 'Network error. Try again.';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
</x-layouts.admin>
