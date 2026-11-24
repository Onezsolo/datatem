<x-layouts.admin title="Shop catalog">
<div class="admin-page min-h-screen bg-surface-muted py-8" x-data="shopAdmin()" x-init="init()">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <a href="/admin/dashboard" class="admin-back text-sm">Admin</a>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Shop catalog</h1>
                <p class="mt-1 text-sm text-muted">Products, categories and delivery orders in the same system as data bundles.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('shop.index') }}" class="inline-flex min-h-10 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted" target="_blank" rel="noopener">View shop</a>
                <button type="button" @click="openProduct()" class="inline-flex min-h-10 items-center rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Add product</button>
            </div>
        </header>

        <div class="mt-5 flex flex-wrap gap-2">
            <button type="button" @click="tab='products'" :class="tab === 'products' ? 'bg-brand-navy text-white border-brand-navy' : 'border-line bg-surface text-muted hover:text-brand-navy'" class="min-h-10 rounded-control border px-4 text-sm font-bold">Products</button>
            <button type="button" @click="tab='categories'" :class="tab === 'categories' ? 'bg-brand-navy text-white border-brand-navy' : 'border-line bg-surface text-muted hover:text-brand-navy'" class="min-h-10 rounded-control border px-4 text-sm font-bold">Categories</button>
            <button type="button" @click="tab='orders'" :class="tab === 'orders' ? 'bg-brand-navy text-white border-brand-navy' : 'border-line bg-surface text-muted hover:text-brand-navy'" class="min-h-10 rounded-control border px-4 text-sm font-bold">Shop orders</button>
            <button type="button" @click="tab='delivery'" :class="tab === 'delivery' ? 'bg-brand-navy text-white border-brand-navy' : 'border-line bg-surface text-muted hover:text-brand-navy'" class="min-h-10 rounded-control border px-4 text-sm font-bold">Delivery</button>
        </div>

        <div x-show="loading" x-cloak class="mt-5 rounded-card border border-line bg-surface p-6 text-sm font-semibold text-muted">Loading catalog...</div>

        <div x-show="!loading && tab === 'products'" class="mt-5 overflow-hidden rounded-card border border-line bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b border-line bg-surface-muted">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Product</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Category</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Price</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Stock</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="product in products" :key="product.id">
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-brand-navy" x-text="product.name"></p>
                                    <p class="text-xs text-muted" x-text="product.sku"></p>
                                </td>
                                <td class="px-4 py-3 text-muted" x-text="product.category ? product.category.name : ''"></td>
                                <td class="px-4 py-3 font-semibold text-brand-navy" x-text="'GHS ' + Number(product.price).toFixed(2)"></td>
                                <td class="px-4 py-3">
                                    <button type="button" @click="adjustStock(product)" class="min-h-8 rounded-control border border-line px-2 text-xs font-bold text-brand-navy hover:bg-surface-muted" x-text="product.stock_quantity"></button>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex min-h-6 items-center rounded-control px-2 text-xs font-bold" :class="product.is_active ? 'bg-brand-yellow text-brand-navy' : 'bg-surface-muted text-muted'" x-text="product.is_active ? 'Active' : 'Hidden'"></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" @click="openProduct(product)" class="min-h-8 rounded-control border border-line px-2 text-xs font-bold text-brand-navy hover:bg-surface-muted">Edit</button>
                                        <button type="button" @click="deleteProduct(product)" class="min-h-8 rounded-control border border-brand-red-strong px-2 text-xs font-bold text-brand-red-strong hover:bg-surface-muted">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p x-show="products.length === 0" class="p-6 text-sm text-muted">No products yet. Add your first product to open the shop.</p>
        </div>

        <div x-show="!loading && tab === 'categories'" class="mt-5 grid gap-4 lg:grid-cols-[1.4fr_1fr]">
            <div class="overflow-hidden rounded-card border border-line bg-surface">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line bg-surface-muted">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Category</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Slug</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="category in categories" :key="category.id">
                            <tr>
                                <td class="px-4 py-3 font-bold text-brand-navy" x-text="category.name"></td>
                                <td class="px-4 py-3 text-muted" x-text="category.slug"></td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" @click="deleteCategory(category)" class="min-h-8 rounded-control border border-brand-red-strong px-2 text-xs font-bold text-brand-red-strong hover:bg-surface-muted">Delete</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <form @submit.prevent="addCategory()" class="rounded-card border border-line bg-surface p-5">
                <h2 class="text-sm font-bold text-brand-navy">New category</h2>
                <label for="category-name" class="mt-3 block text-sm font-bold text-brand-navy">Name</label>
                <input id="category-name" type="text" x-model="categoryName" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                <label for="category-sort" class="mt-3 block text-sm font-bold text-brand-navy">Sort order</label>
                <input id="category-sort" type="number" min="0" x-model.number="categorySort" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                <button type="submit" class="mt-4 min-h-10 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Add category</button>
            </form>
        </div>

        <div x-show="!loading && tab === 'orders'" class="mt-5 overflow-hidden rounded-card border border-line bg-surface">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-line bg-surface-muted">
                        <tr>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Order</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Customer</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Total</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Payment</th>
                            <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="order in orders" :key="order.id">
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-bold text-brand-navy" x-text="order.order_number"></p>
                                    <p class="text-xs text-muted" x-text="order.items_count + ' item(s)'"></p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-brand-navy" x-text="order.customer_name"></p>
                                    <p class="text-xs text-muted" x-text="order.customer_phone"></p>
                                </td>
                                <td class="px-4 py-3 font-semibold text-brand-navy" x-text="(order.currency || 'GHS') + ' ' + Number(order.total).toFixed(2)"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex min-h-6 items-center rounded-control bg-surface-muted px-2 text-xs font-bold capitalize text-brand-navy" x-text="order.payment_status"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex min-h-6 items-center rounded-control bg-brand-yellow px-2 text-xs font-bold capitalize text-brand-navy" x-text="order.status"></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <select @change="updateOrder(order, $event.target.value)" class="h-9 rounded-control border border-line px-2 text-xs font-semibold text-brand-navy">
                                        <option value="">Move to...</option>
                                        <option value="processing">Processing</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <p x-show="orders.length === 0" class="p-6 text-sm text-muted">No shop orders yet.</p>
        </div>

        <form x-show="!loading && tab === 'delivery'" @submit.prevent="saveDelivery()" class="mt-5 rounded-card border border-line bg-surface p-5">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-extrabold text-brand-navy">Delivery zones</h2>
                    <p class="mt-1 text-sm text-muted">Fees shown at checkout. Changes apply to new orders.</p>
                </div>
                <button type="submit" :disabled="deliverySaving" class="min-h-10 rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong disabled:opacity-60" x-text="deliverySaving ? 'Saving...' : 'Save delivery settings'"></button>
            </div>

            <div class="mt-4 space-y-3">
                <template x-for="(zone, key) in deliveryZones" :key="key">
                    <div class="grid gap-3 sm:grid-cols-[1fr_8rem_10rem] sm:items-end">
                        <div>
                            <label :for="'zone-label-' + key" class="block text-sm font-bold text-brand-navy">Label</label>
                            <input :id="'zone-label-' + key" type="text" x-model="zone.label" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div>
                            <label :for="'zone-fee-' + key" class="block text-sm font-bold text-brand-navy">Fee (GHS)</label>
                            <input :id="'zone-fee-' + key" type="number" step="0.01" min="0" x-model.number="zone.fee" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <label class="flex min-h-11 items-center gap-2 text-sm font-medium text-muted">
                            <input type="radio" name="default_zone" :value="key" x-model="deliveryDefault" class="h-4 w-4 border-line">
                            Default zone
                        </label>
                    </div>
                </template>
            </div>

            <div class="mt-4 max-w-xs">
                <label for="service-charge" class="block text-sm font-bold text-brand-navy">Service charge (GHS)</label>
                <input id="service-charge" type="number" step="0.01" min="0" x-model.number="deliveryService" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
            </div>
        </form>
    </div>

    <div x-show="productModal" x-cloak class="fixed inset-0 z-[120] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="product-modal-title">
        <div class="fixed inset-0 bg-brand-navy/60" @click="productModal = false"></div>
        <div class="relative flex min-h-screen items-center justify-center p-4">
            <form @submit.prevent="saveProduct()" class="relative w-full max-w-lg rounded-card border border-line bg-surface p-5">
                <h2 id="product-modal-title" class="text-lg font-extrabold text-brand-navy" x-text="productForm.id ? 'Edit product' : 'Add product'"></h2>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="product-name" class="block text-sm font-bold text-brand-navy">Name</label>
                        <input id="product-name" type="text" x-model="productForm.name" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="product-category" class="block text-sm font-bold text-brand-navy">Category</label>
                        <select id="product-category" x-model.number="productForm.category_id" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                            <template x-for="category in categories" :key="category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="product-sku" class="block text-sm font-bold text-brand-navy">SKU</label>
                        <input id="product-sku" type="text" x-model="productForm.sku" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="product-price" class="block text-sm font-bold text-brand-navy">Price (GHS)</label>
                        <input id="product-price" type="number" step="0.01" min="0" x-model="productForm.price" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="product-stock" class="block text-sm font-bold text-brand-navy">Stock</label>
                        <input id="product-stock" type="number" min="0" x-model.number="productForm.stock_quantity" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="product-short" class="block text-sm font-bold text-brand-navy">Short description</label>
                        <input id="product-short" type="text" x-model="productForm.short_description" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="product-active" type="checkbox" x-model="productForm.is_active" class="h-4 w-4 rounded-control border-line">
                        <label for="product-active" class="text-sm font-medium text-muted">Active in shop</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="product-featured" type="checkbox" x-model="productForm.is_featured" class="h-4 w-4 rounded-control border-line">
                        <label for="product-featured" class="text-sm font-medium text-muted">Featured</label>
                    </div>
                </div>

                <div x-show="productForm.id" class="mt-5 border-t border-line pt-4">
                    <h3 class="text-sm font-bold text-brand-navy">Images</h3>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <template x-for="image in (productForm.images || [])" :key="image.id">
                            <div class="relative">
                                <img :src="'/storage/' + image.path" :alt="image.alt_text || productForm.name" class="h-16 w-16 rounded-control border border-line object-cover">
                                <button type="button" @click="deleteImage(image)" class="absolute -right-1 -top-1 inline-flex h-6 w-6 items-center justify-center rounded-control bg-brand-red-strong text-white" aria-label="Delete image">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <p x-show="!(productForm.images || []).length" class="text-sm text-muted">No images yet.</p>
                    </div>
                    <label class="mt-3 inline-flex min-h-10 cursor-pointer items-center rounded-control border border-line px-3 text-sm font-bold text-brand-navy hover:bg-surface-muted">
                        Upload image
                        <input type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" @change="uploadImage($event)">
                    </label>
                </div>

                <div x-show="productForm.id" class="mt-5 border-t border-line pt-4">
                    <h3 class="text-sm font-bold text-brand-navy">Variants</h3>
                    <ul x-show="(productForm.variants || []).length" class="mt-2 divide-y divide-line rounded-control border border-line">
                        <template x-for="variant in (productForm.variants || [])" :key="variant.id">
                            <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm">
                                <span><span class="font-bold text-brand-navy" x-text="variant.name"></span> <span class="text-muted" x-text="variant.sku"></span></span>
                                <span class="text-muted" x-text="'GHS ' + Number(variant.price || 0).toFixed(2)"></span>
                                <button type="button" @click="deleteVariant(variant)" class="text-xs font-bold text-brand-red-strong">Delete</button>
                            </li>
                        </template>
                    </ul>
                    <p x-show="!(productForm.variants || []).length" class="mt-2 text-sm text-muted">No variants. Add one below.</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-4">
                        <input type="text" x-model="variantForm.name" placeholder="Variant name" class="h-10 rounded-control border border-line px-2 text-sm">
                        <input type="text" x-model="variantForm.sku" placeholder="SKU" class="h-10 rounded-control border border-line px-2 text-sm">
                        <input type="number" step="0.01" min="0" x-model.number="variantForm.price" placeholder="Price" class="h-10 rounded-control border border-line px-2 text-sm">
                        <input type="number" min="0" x-model.number="variantForm.stock_quantity" placeholder="Stock" class="h-10 rounded-control border border-line px-2 text-sm">
                    </div>
                    <button type="button" @click="addVariant()" class="mt-2 min-h-10 rounded-control bg-brand-navy px-3 text-sm font-bold text-white hover:bg-brand-navy-soft">Add variant</button>
                </div>

                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button type="button" @click="productModal = false" class="min-h-10 rounded-control border border-line px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Cancel</button>
                    <button type="submit" :disabled="saving" class="min-h-10 rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong disabled:opacity-60" x-text="saving ? 'Saving...' : 'Save product'"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function shopAdmin() {
    return {
        tab: 'products',
        loading: true,
        saving: false,
        products: [],
        categories: [],
        orders: [],
        productModal: false,
        productForm: {},
        categoryName: '',
        categorySort: 0,
        deliveryZones: {},
        deliveryDefault: '',
        deliveryService: 0,
        deliverySaving: false,
        variantForm: { name: '', sku: '', price: '', stock_quantity: 0 },

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
                throw new Error(firstError || data.message || 'Request failed.');
            }
            return data;
        },

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.load();
            await this.loadDelivery();
        },

        async load() {
            this.loading = true;
            try {
                const [products, categories, orders] = await Promise.all([
                    this.api('/api/admin/shop/products'),
                    this.api('/api/admin/shop/categories'),
                    this.api('/api/admin/shop/orders')
                ]);
                this.products = products.data || [];
                this.categories = categories.categories || [];
                this.orders = orders.data || [];
            } catch (error) {
                window.showAlert(error.message);
            } finally {
                this.loading = false;
            }
        },

        openProduct(product = null) {
            this.productForm = product ? {
                ...product,
                images: product.images || [],
                variants: product.variants || []
            } : {
                category_id: this.categories[0] ? this.categories[0].id : '',
                name: '',
                sku: '',
                price: '',
                stock_quantity: 0,
                short_description: '',
                is_active: true,
                is_featured: false
            };
            this.productModal = true;
        },

        async saveProduct() {
            this.saving = true;
            try {
                const id = this.productForm.id;
                const url = id ? '/api/admin/shop/products/' + id : '/api/admin/shop/products';
                await this.api(url, {
                    method: id ? 'PUT' : 'POST',
                    body: JSON.stringify(this.productForm)
                });
                this.productModal = false;
                await this.load();
                window.showAlert('Product saved.');
            } catch (error) {
                window.showAlert(error.message);
            } finally {
                this.saving = false;
            }
        },

        async deleteProduct(product) {
            if (!window.confirm('Delete ' + product.name + '?')) return;
            try {
                await this.api('/api/admin/shop/products/' + product.id, { method: 'DELETE' });
                await this.load();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async adjustStock(product) {
            const value = window.prompt('Stock change for ' + product.name + ' (e.g. 10 or -3):', '0');
            if (!value) return;
            try {
                await this.api('/api/admin/shop/products/' + product.id + '/stock', {
                    method: 'POST',
                    body: JSON.stringify({ quantity: parseInt(value, 10), reason: 'Admin adjustment' })
                });
                await this.load();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async addCategory() {
            try {
                await this.api('/api/admin/shop/categories', {
                    method: 'POST',
                    body: JSON.stringify({ name: this.categoryName, sort_order: Number(this.categorySort) || 0 })
                });
                this.categoryName = '';
                this.categorySort = 0;
                await this.load();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async deleteCategory(category) {
            if (!window.confirm('Delete ' + category.name + '?')) return;
            try {
                await this.api('/api/admin/shop/categories/' + category.id, { method: 'DELETE' });
                await this.load();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async updateOrder(order, status) {
            if (!status) return;
            try {
                await this.api('/api/admin/shop/orders/' + order.id + '/status', {
                    method: 'PUT',
                    body: JSON.stringify({ status })
                });
                await this.load();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async refreshProduct() {
            const list = await this.api('/api/admin/shop/products');
            this.products = list.data || [];
            const fresh = this.products.find((product) => product.id === this.productForm.id);
            if (fresh) {
                this.productForm = { ...fresh, images: fresh.images || [], variants: fresh.variants || [] };
            }
        },

        async uploadImage(event) {
            const file = event.target.files && event.target.files[0];
            if (!file || !this.productForm.id) return;

            const form = new FormData();
            form.append('image', file);
            form.append('alt_text', this.productForm.name || '');

            try {
                const response = await fetch('/api/admin/shop/products/' + this.productForm.id + '/images', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json'
                    },
                    body: form
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(data.message || 'Upload failed.');
                await this.refreshProduct();
                window.showAlert('Image uploaded.');
            } catch (error) {
                window.showAlert(error.message);
            } finally {
                event.target.value = '';
            }
        },

        async deleteImage(image) {
            if (!window.confirm('Delete this image?')) return;
            try {
                await this.api('/api/admin/shop/images/' + image.id, { method: 'DELETE' });
                await this.refreshProduct();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async addVariant() {
            if (!this.productForm.id) return;
            try {
                await this.api('/api/admin/shop/products/' + this.productForm.id + '/variants', {
                    method: 'POST',
                    body: JSON.stringify(this.variantForm)
                });
                this.variantForm = { name: '', sku: '', price: '', stock_quantity: 0 };
                await this.refreshProduct();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async deleteVariant(variant) {
            if (!window.confirm('Delete variant ' + variant.name + '?')) return;
            try {
                await this.api('/api/admin/shop/variants/' + variant.id, { method: 'DELETE' });
                await this.refreshProduct();
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async loadDelivery() {
            try {
                const data = await this.api('/api/admin/shop/delivery');
                this.deliveryZones = data.zones || {};
                this.deliveryDefault = data.default_zone || '';
                this.deliveryService = Number(data.service_charge || 0);
            } catch (error) {
                window.showAlert(error.message);
            }
        },

        async saveDelivery() {
            this.deliverySaving = true;
            try {
                const zones = {};
                Object.entries(this.deliveryZones).forEach(([key, zone]) => {
                    zones[key] = { label: zone.label, fee: Number(zone.fee || 0) };
                });
                const data = await this.api('/api/admin/shop/delivery', {
                    method: 'PUT',
                    body: JSON.stringify({
                        zones,
                        default_zone: this.deliveryDefault,
                        service_charge: Number(this.deliveryService || 0)
                    })
                });
                this.deliveryZones = data.zones || zones;
                this.deliveryDefault = data.default_zone || this.deliveryDefault;
                this.deliveryService = Number(data.service_charge || 0);
                window.showAlert('Delivery settings saved.');
            } catch (error) {
                window.showAlert(error.message);
            } finally {
                this.deliverySaving = false;
            }
        }
    };
}
</script>
</x-layouts.admin>
