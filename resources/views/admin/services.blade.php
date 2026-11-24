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

        @if (auth()->user()?->isDeveloper())
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

        <section class="mt-6 rounded-card border border-line bg-surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-brand-navy">Provider sources</h2>
                    <p class="mt-1 text-sm text-muted">The default source is preconfigured from the environment. Add any compatible source, choose its services and which source is the default.</p>
                </div>
                <button type="button" @click="openProvider()" class="min-h-10 rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Add source</button>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <template x-for="provider in providers" :key="provider.id">
                    <div class="rounded-control border border-line bg-surface-muted p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-brand-navy" x-text="provider.name"></p>
                                <p class="mt-0.5 break-all text-xs text-muted" x-text="provider.base_url"></p>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <span x-show="provider.is_default" class="inline-flex min-h-6 items-center rounded-control bg-brand-yellow px-2 text-[0.6875rem] font-bold text-brand-navy">Default</span>
                                <span class="inline-flex min-h-6 items-center rounded-control px-2 text-[0.6875rem] font-bold" :class="provider.is_active ? 'bg-surface text-brand-navy border border-line' : 'bg-surface text-brand-red-strong border border-brand-red-strong'" x-text="provider.is_active ? 'Active' : 'Inactive'"></span>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-1">
                            <template x-for="service in (provider.services || [])" :key="service">
                                <span class="inline-flex min-h-5 items-center rounded-control bg-surface px-1.5 text-[0.625rem] font-semibold text-muted" x-text="types[service] || service"></span>
                            </template>
                        </div>

                        <p class="mt-2 text-xs text-muted" x-show="provider.masked_key" x-text="'Key ' + provider.masked_key"></p>
                        <p class="mt-1 text-xs text-muted" x-show="provider.last_synced_at" x-text="'Last sync ' + new Date(provider.last_synced_at).toLocaleString()"></p>
                        <p class="mt-1 text-xs font-semibold text-brand-red-strong" x-show="provider.last_sync_error" x-text="provider.last_sync_error"></p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" @click="syncProvider(provider)" class="min-h-8 rounded-control bg-brand-yellow px-2.5 text-xs font-bold text-brand-navy hover:bg-brand-yellow-strong">Sync</button>
                            <button type="button" x-show="!provider.is_default" @click="setDefaultProvider(provider)" class="min-h-8 rounded-control border border-line bg-surface px-2.5 text-xs font-bold text-brand-navy hover:bg-surface">Make default</button>
                            <button type="button" @click="openProvider(provider)" class="min-h-8 rounded-control border border-line bg-surface px-2.5 text-xs font-bold text-brand-navy hover:bg-surface">Edit</button>
                            <button type="button" @click="deleteProvider(provider)" class="min-h-8 rounded-control border border-brand-red-strong px-2.5 text-xs font-bold text-brand-red-strong hover:bg-surface">Delete</button>
                        </div>
                    </div>
                </template>
                <p x-show="providers.length === 0" class="text-sm text-muted">No sources yet. The default source is created automatically on first sync or by the seeder.</p>
            </div>
        </section>

        @endif

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

        <div x-show="providerModal" x-cloak class="fixed inset-0 z-[125] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="provider-modal-title">
            <div class="fixed inset-0 bg-brand-navy/60" @click="providerModal = false"></div>
            <div class="relative flex min-h-screen items-center justify-center p-4">
                <form @submit.prevent="saveProvider()" class="relative w-full max-w-lg rounded-card border border-line bg-surface p-5">
                    <h2 id="provider-modal-title" class="text-lg font-extrabold text-brand-navy" x-text="providerEditing ? 'Edit source' : 'Add source'"></h2>
                    <p class="mt-1 text-xs text-muted">Leave key fields blank to keep the existing credentials when editing.</p>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="provider-name" class="block text-sm font-bold text-brand-navy">Name</label>
                            <input id="provider-name" type="text" x-model="providerForm.name" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="provider-url" class="block text-sm font-bold text-brand-navy">Base URL</label>
                            <input id="provider-url" type="url" x-model="providerForm.base_url" required placeholder="https://provider.example/api/v1" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div>
                            <label for="provider-key" class="block text-sm font-bold text-brand-navy">API key</label>
                            <input id="provider-key" type="text" x-model="providerForm.api_key" autocomplete="off" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div>
                            <label for="provider-secret" class="block text-sm font-bold text-brand-navy">API secret</label>
                            <input id="provider-secret" type="password" x-model="providerForm.api_secret" autocomplete="new-password" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div>
                            <label for="provider-sort" class="block text-sm font-bold text-brand-navy">Priority</label>
                            <input id="provider-sort" type="number" min="0" max="1000" x-model.number="providerForm.sort_order" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div class="flex flex-col justify-end gap-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-brand-navy">
                                <input type="checkbox" x-model="providerForm.is_active" class="h-4 w-4 rounded-control border-line"> Active
                            </label>
                            <label class="flex items-center gap-2 text-sm font-semibold text-brand-navy">
                                <input type="checkbox" x-model="providerForm.is_default" class="h-4 w-4 rounded-control border-line"> Default source
                            </label>
                        </div>
                    </div>

                    <fieldset class="mt-4">
                        <legend class="text-sm font-bold text-brand-navy">Services served</legend>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <template x-for="(label, key) in types" :key="key">
                                <label class="flex min-h-10 items-center gap-2 rounded-control border border-line px-3 text-sm font-semibold text-brand-navy">
                                    <input type="checkbox" :value="key" x-model="providerForm.services" class="h-4 w-4 rounded-control border-line">
                                    <span x-text="label"></span>
                                </label>
                            </template>
                        </div>
                    </fieldset>

                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button type="button" @click="providerModal = false" class="min-h-10 rounded-control border border-line px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Cancel</button>
                        <button type="submit" :disabled="providerSaving" class="min-h-10 rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong disabled:opacity-60" x-text="providerSaving ? 'Saving...' : 'Save source'"></button>
                    </div>
                </form>
            </div>
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
        providers: [],
        providerModal: false,
        providerEditing: null,
        providerSaving: false,
        providerForm: {},
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
                this.providers = data.providers || [];
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


        openProvider(provider = null) {
            this.providerEditing = provider ? provider.id : null;
            this.providerForm = provider ? {
                name: provider.name,
                driver: provider.driver || 'patupaa',
                base_url: provider.base_url,
                api_key: '',
                api_secret: '',
                services: (provider.services || Object.keys(this.types)),
                is_active: !!provider.is_active,
                is_default: !!provider.is_default,
                sort_order: provider.sort_order || 0
            } : {
                name: '',
                driver: 'patupaa',
                base_url: '',
                api_key: '',
                api_secret: '',
                services: Object.keys(this.types),
                is_active: true,
                is_default: false,
                sort_order: 0
            };
            this.providerModal = true;
        },

        async saveProvider() {
            this.providerSaving = true;
            try {
                const id = this.providerEditing;
                const url = id ? '/api/admin/services/providers/' + id : '/api/admin/services/providers';
                await this.api(url, {
                    method: id ? 'PUT' : 'POST',
                    body: JSON.stringify(this.providerForm)
                });
                this.providerModal = false;
                await this.load();
                window.showAlert('Source saved.');
            } catch (error) {
                window.showAlert(error.message);
            } finally {
                this.providerSaving = false;
            }
        },

        async deleteProvider(provider) {
            if (!window.confirm('Delete source ' + provider.name + '? Its synced items will be removed.')) return;
            try {
                await this.api('/api/admin/services/providers/' + provider.id, { method: 'DELETE' });
                await this.load();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async setDefaultProvider(provider) {
            try {
                await this.api('/api/admin/services/providers/' + provider.id + '/default', { method: 'POST' });
                await this.load();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async syncProvider(provider) {
            try {
                const data = await this.api('/api/admin/services/providers/' + provider.id + '/sync', { method: 'POST' });
                await this.load();
                const parts = Object.entries(data.summary || {}).map(([type, counts]) => (this.types[type] || type) + ': ' + counts.synced + ' synced, ' + counts.disabled + ' off');
                window.showAlert(parts.join('\n') || 'Source synced.');
            } catch (error) {
                window.showAlert(error.message);
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
