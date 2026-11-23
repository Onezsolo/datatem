@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-12" x-data="notificationsPage()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8">
            <h1 class="text-3xl font-black text-slate-900 mb-4 md:mb-0">Notifications</h1>
            <div class="flex items-center space-x-3">
                <button x-show="notifications.length > 0 && unreadCount > 0" @click="markAllRead()" class="text-sm font-bold text-primary-600 hover:underline">
                    Mark all as read
                </button>
                <button @click="fetchNotifications()" class="text-sm font-bold text-slate-500 hover:text-slate-700 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>
        </div>

        <div x-show="loading" class="text-center py-16">
            <div class="animate-spin w-10 h-10 border-4 border-primary-600 border-t-transparent rounded-full mx-auto mb-4"></div>
            <p class="text-slate-500 font-bold">Loading notifications...</p>
        </div>

        <div x-show="!loading && notifications.length === 0" class="text-center py-16 bg-white rounded-[32px] border border-slate-200">
            <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <h2 class="text-xl font-black text-slate-900 mb-2">No notifications yet</h2>
            <p class="text-slate-500 font-medium">When you buy data or top up your wallet, updates will appear here.</p>
        </div>

        <div x-show="!loading && notifications.length > 0" class="space-y-4">
            <template x-for="n in notifications" :key="n.id">
                <div @click="openNotification(n)" :class="n.read_at ? 'bg-white border-slate-100' : 'bg-primary-50/30 border-primary-100'" class="p-6 rounded-2xl shadow-sm border flex items-start space-x-4 cursor-pointer hover:shadow-md transition-all">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                         :class="{
                             'bg-emerald-100 text-emerald-600': n.type === 'success',
                             'bg-amber-100 text-amber-600': n.type === 'warning',
                             'bg-purple-100 text-purple-600': n.type === 'info',
                             'bg-rose-100 text-rose-600': n.type === 'critical'
                         }">
                        <template x-if="n.type === 'success'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                        <template x-if="n.type !== 'success'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-slate-900" x-text="n.title"></h3>
                        <p class="text-sm text-slate-600 mt-1" x-text="n.message"></p>
                        <span class="text-xs text-slate-400 mt-2 block" x-text="n.created_at_human"></span>
                    </div>
                    <span x-show="!n.read_at" class="w-2.5 h-2.5 bg-primary-500 rounded-full flex-shrink-0 mt-2"></span>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function notificationsPage() {
    return {
        notifications: [],
        unreadCount: 0,
        loading: true,
        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchNotifications();
        },
        async fetchNotifications() {
            this.loading = true;
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
                this.loading = false;
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
        }
    };
}
</script>
@endsection
