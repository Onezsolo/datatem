<x-layouts.admin title="Services">
<div class="min-h-screen bg-surface-muted py-8" x-data="servicesAdmin()" x-init="init()">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="/admin/dashboard" class="text-sm font-semibold text-brand-navy">Admin</a>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Services catalog</h1>
                <p class="mt-1 text-sm text-muted">Provider catalog mirror: data, subscriptions, WAEC, utility and AFA. Turn items off here or let provider sync disable them.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex min-h-8 items-center rounded-control px-2.5 text-xs font-bold" :class="configured ? 'bg-brand-yellow text-brand-navy' : 'bg-surface text-brand-red-strong border border-brand-red-strong'" x-text="configured ? 'Provider API connected' : 'Provider API not configured'"></span>
                <button type="button" @click="syncNow()" :disabled="syncing" class="min-h-10 rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong disabled:opacity-60" x-text="syncing ? 'Syncing...' : 'Sync from provider'"></button>
            </div>
        </header>

        <section class="mt-6 rounded-card border border-line bg-surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-brand-navy">Service availability</h2>
                    <p class="mt-1 text-sm text-muted">Global switches. Item-level switches below are manual overrides; provider sync controls remote availability.</p>
                </div>
                <button type="button" @click="saveToggles()" :disabled="savingToggles" class="min-h-10 rounded-control border border-line px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted disabled:opacity-60" x-text="savingToggles ? 'Saving...' : 'Save switches'"></button>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <div class="sm:col-span-2 lg:col-span-1">
                    <label for="afa-price" class="block text-sm font-bold text-brand-navy">AFA price (GHS)</label>
                    <input id="afa-price" type="number" step="0.01" min="0" x-model.number="afaPrice" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    <p class="mt-1 text-xs text-muted">0 disables AFA registration.</p>
                </div>
                <template x-for="(label, key) in types" :key="key">
                    <label class="flex min-h-11 items-center gap-2 rounded-control border border-line bg-surface-muted px-3 text-sm font-semibold text-brand-navy">
                        <input type="checkbox" x-model="toggles[key]" class="h-4 w-4 rounded-control border-line">
                        <span x-text="label"></span>
                    </label>
                </template>
            </div>
        </section>

        <div class="mt-6 overflow-hidden rounded-card border border-line bg-surface">
            <div class="flex flex-wrap items-center gap-3 border-b border-line p-4">
                <div class="min-w-0 flex-1">
                    <label for="service-search" class="sr-only">Search catalog</label>
                    <input id="service-search" type="search" x-model="search" @input.debounce.400ms="load()" placeholder="Search name or plan code" class="h-11 w-full max-w-sm rounded-control border border-line px-3 text-sm">
                </div>
                <label for="service-type" class="sr-only">Filter type</label>
                <select id="service-type" x-model="typeFilter" @change="load()" class="h-11 rounded-control border border-line px-3 text-sm font-semibold text-brand-navy">
                    <option value="">All types</option>
                    <template x-for="(label, key) in types" :key="key">
                        <option :value="key" x-text="label"></option>
                    </template>
                </select>
                <span class="text-sm font-semibold text-muted" x-text="total + ' item(s)'"></span>
            </div>

            <div x-show="loading" x-cloak class="p-6 text-sm font-semibold text-muted">Loading catalog...</div>

            <div class="overflow-x-auto" x-show="!loading">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-line bg-surface-muted">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Item</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Type</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Plan code</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Price</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Provider</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Admin on/off</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="item in items" :key="item.id">
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-brand-navy" x-text="item.name"></p>
                                    <p class="text-xs text-muted" x-text="[item.network, item.data_value, item.validity].filter(Boolean).join(' · ')"></p>
                                </td>
                                <td class="px-4 py-3 text-muted" x-text="types[item.remote_type] || item.remote_type"></td>
                                <td class="px-4 py-3 text-muted" x-text="item.plan_code || '—'"></td>
                                <td class="px-4 py-3 font-semibold text-brand-navy" x-text="'GHS ' + Number(item.price).toFixed(2)"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex min-h-6 items-center rounded-control px-2 text-xs font-bold" :class="item.remote_enabled ? 'bg-brand-yellow text-brand-navy' : 'bg-surface-muted text-muted'" x-text="item.remote_enabled ? 'In' : 'Off'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-muted">
                                        <input type="checkbox" :checked="item.admin_enabled" @change="toggleItem(item, $event.target.checked)" class="h-4 w-4 rounded-control border-line">
                                        <span x-text="item.admin_enabled ? 'On' : 'Off'"></span>
                                    </label>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p x-show="!loading && items.length === 0" class="p-6 text-sm text-muted">No catalog items yet. Run a provider sync.</p>
        </div>
    </div>
</div>

<script>
function servicesAdmin() {
    return {
        loading: true,
        syncing: false,
        savingToggles: false,
        items: [],
        total: 0,
        toggles: {},
        types: {},
        configured: false,
        afaPrice: 0,
        search: '',
        typeFilter: '',

        get headers() {
            return {
                'Authorization': 'Bearer ' + this.$store.auth.token,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            };
        },

        async api(url, options = {}) {
            const response = await fetch(url, { headers: this.headers, ...options });
            if (response.status === 401) {
                this.$store.auth.logout();
                throw new Error('Session expired.');
            }
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(firstError || (data.error && data.error.message) || data.message || 'Request failed.');
            }
            return data;
        },

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.load();
        },

        async load() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.search) params.set('search', this.search);
                if (this.typeFilter) params.set('type', this.typeFilter);

                const data = await this.api('/api/admin/services?' + params.toString());
                this.items = data.items.data || [];
                this.total = data.items.total || 0;
                this.toggles = data.toggles || {};
                this.types = data.types || {};
                this.configured = !!data.configured;
                this.afaPrice = Number(data.afa_price || 0);
            } catch (error) {
                window.showAlert(error.message);
            } finally {
                this.loading = false;
            }
        },

        async syncNow() {
            this.syncing = true;
            try {
                const data = await this.api('/api/admin/services/sync', { method: 'POST' });
                await this.load();
                const parts = Object.entries(data.summary || {}).map(([type, counts]) => (this.types[type] || type) + ': ' + counts.synced + ' synced, ' + counts.disabled + ' off');
                window.showAlert(parts.join('\n') || 'Catalog synced.');
            } catch (error) {
                window.showAlert(error.message);
            } finally {
                this.syncing = false;
            }
        },

        async toggleItem(item, enabled) {
            try {
                await this.api('/api/admin/services/catalog/' + item.id, {
                    method: 'PUT',
                    body: JSON.stringify({ admin_enabled: enabled })
                });
                item.admin_enabled = enabled;
            } catch (error) {
                window.showAlert(error.message);
                await this.load();
            }
        },

        async saveToggles() {
            this.savingToggles = true;
            try {
                const data = await this.api('/api/admin/services/toggles', {
                    method: 'PUT',
                    body: JSON.stringify({ toggles: this.toggles, afa_price: this.afaPrice })
                });
                this.toggles = data.toggles || this.toggles;
                this.afaPrice = Number(data.afa_price || this.afaPrice);
                window.showAlert('Service switches saved.');
            } catch (error) {
                window.showAlert(error.message);
            } finally {
                this.savingToggles = false;
            }
        }
    };
}
</script>
</x-layouts.admin>
