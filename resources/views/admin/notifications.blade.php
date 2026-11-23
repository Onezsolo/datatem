@extends('layouts.app')

@section('content')
<div class="admin-page min-h-screen bg-gray-50 py-8" x-data="adminNotificationsPage()" x-init="init()">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="/admin/dashboard" class="admin-back mb-6 inline-flex items-center text-sm">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>

        <div class="mb-10">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-900">My Notifications</h2>
                <div class="flex items-center gap-3">
                    <button x-show="notifications.length > 0 && unreadCount > 0" @click="markAllRead()" class="text-sm font-semibold text-cyan-600 hover:underline">Mark all read</button>
                    <button @click="fetchNotifications()" class="text-sm font-bold text-slate-500 hover:text-slate-700 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            <div x-show="notificationsLoading" class="text-center py-8">
                <div class="animate-spin w-8 h-8 border-4 border-cyan-600 border-t-transparent rounded-full mx-auto mb-2"></div>
                <p class="text-slate-500 text-sm font-bold">Loading...</p>
            </div>

            <div x-show="!notificationsLoading && notifications.length === 0" class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <p class="text-slate-500 font-medium">No notifications yet. Data orders, topups, and complaints will appear here.</p>
            </div>

            <div x-show="!notificationsLoading && notifications.length > 0" class="space-y-3">
                <template x-for="n in notifications" :key="n.id">
                    <div @click="openNotification(n)" :class="n.read_at ? 'bg-white border-slate-100' : 'bg-cyan-50/50 border-cyan-100'" class="p-5 rounded-2xl border shadow-sm flex items-start space-x-4 cursor-pointer hover:shadow-md transition-all">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             :class="{ 'bg-emerald-100 text-emerald-600': n.type === 'success', 'bg-amber-100 text-amber-600': n.type === 'warning', 'bg-purple-100 text-purple-600': n.type === 'info', 'bg-rose-100 text-rose-600': n.type === 'critical' }">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900" x-text="n.title"></h3>
                            <p class="text-sm text-slate-600 mt-0.5" x-text="n.message"></p>
                            <span class="text-xs text-slate-400 mt-1 block" x-text="n.created_at_human"></span>
                        </div>
                        <span x-show="!n.read_at" class="w-2 h-2 bg-cyan-500 rounded-full flex-shrink-0 mt-2"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- System Alerts (broadcast) -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">System <span class="gradient-text">Alerts</span></h1>
            <p class="text-slate-500 mt-2 font-medium">Broadcast important updates to all platform users.</p>
        </div>

        <div class="admin-card p-8">
            <form @submit.prevent="send" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Alert Title</label>
                    <input type="text" x-model="title" required placeholder="e.g. System Maintenance" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 outline-none transition-all font-medium text-slate-900">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Message Body</label>
                    <textarea x-model="message" required rows="4" placeholder="Type your broadcast message here..." class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 outline-none transition-all font-medium text-slate-900"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Channels</label>
                    <p class="text-xs text-slate-500 mb-2">Choose how to deliver: in-app notification, email, and/or SMS.</p>
                    <div class="flex flex-wrap gap-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="channels.in_app" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="font-medium text-slate-700">In-app</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="channels.email" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="font-medium text-slate-700">Email</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="channels.sms" class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="font-medium text-slate-700">SMS</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Send to</label>
                    <div class="flex flex-wrap gap-4 mb-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="audience" value="all" class="text-cyan-600 focus:ring-cyan-500">
                            <span class="font-medium text-slate-700">All users</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="audience" value="resellers" class="text-cyan-600 focus:ring-cyan-500">
                            <span class="font-medium text-slate-700">Resellers only</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" x-model="audience" value="users" class="text-cyan-600 focus:ring-cyan-500">
                            <span class="font-medium text-slate-700">Selected users</span>
                        </label>
                    </div>
                    <div x-show="audience === 'users'" class="mt-2">
                        <select x-model="userIds" multiple class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none font-medium text-slate-900" style="min-height: 120px;">
                            <template x-for="u in recipientList" :key="u.id">
                                <option :value="u.id" x-text="u.full_name + ' (' + (u.email || u.phone || '') + ')'"></option>
                            </template>
                        </select>
                        <p class="text-xs text-slate-500 mt-1">Hold Ctrl/Cmd to select multiple.</p>
                    </div>
                </div>
                <div x-show="sendError" class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm" x-text="sendError"></div>
                <div x-show="sendSuccess" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm" x-text="sendSuccess"></div>
                <div class="pt-6">
                    <button type="submit" :disabled="loading" class="w-full btn-admin-primary py-4 rounded-xl flex items-center justify-center gap-2 disabled:opacity-70">
                        <span x-text="loading ? 'Sending...' : 'Send Announcement'"></span>
                        <svg x-show="!loading" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.167H3.38a1.345 1.345 0 01-1.35-1.108l-.51-4.139a1.345 1.345 0 011.057-1.479l7.332-1.294a1.76 1.76 0 011.883 1.536zm0 0l6.27-6.27m0 0a2.09 2.09 0 112.956 2.956L14 8.27m6.27-6.27L20 8.27M13 19.27h.01"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function adminNotificationsPage() {
    return {
        title: '',
        message: '',
        type: 'info',
        channels: { in_app: true, email: false, sms: false },
        audience: 'all',
        userIds: [],
        recipientList: [],
        sendError: '',
        sendSuccess: '',
        loading: false,
        notifications: [],
        unreadCount: 0,
        notificationsLoading: true,

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchNotifications();
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

        async fetchNotifications() {
            this.notificationsLoading = true;
            try {
                const response = await fetch('{{ url("/api/auth/user/notifications") }}', {
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.notifications = data.data || [];
                    this.unreadCount = data.unread_count || 0;
                }
            } catch (e) {
                console.error('Failed to fetch notifications:', e);
            } finally {
                this.notificationsLoading = false;
            }
        },

        openNotification(n) {
            if (!n.read_at) this.markRead(n.id);
            if (n.url) window.location.href = n.url;
        },

        async markRead(id) {
            try {
                const response = await fetch('{{ url("/api/auth/user/notifications") }}/' + id + '/read', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.unreadCount = data.unread_count ?? this.unreadCount;
                    const idx = this.notifications.findIndex(x => x.id === id);
                    if (idx >= 0) this.notifications[idx].read_at = new Date().toISOString();
                }
            } catch (e) {}
        },

        async markAllRead() {
            try {
                const response = await fetch('{{ url("/api/auth/user/notifications/read-all") }}', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json'
                    }
                });
                if (response.ok) {
                    this.unreadCount = 0;
                    this.notifications.forEach(n => { n.read_at = n.read_at || new Date().toISOString(); });
                }
            } catch (e) {}
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
@endsection
