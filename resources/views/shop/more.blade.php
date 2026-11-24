<x-layouts.public title="More" description="Order tracking, policies and support links.">
    <div class="container-page py-10">
        <div class="mx-auto max-w-xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">More</h1>
            <p class="mt-2 text-sm leading-relaxed text-muted">Order tracking, store policies and support links.</p>

            <ul class="mt-6 divide-y divide-line rounded-card border border-line bg-surface">
                <li><a href="{{ route('shop.track') }}" class="flex items-center justify-between gap-3 p-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Track an order <x-ui.icon name="chevron-right" :size="16" class="text-muted" /></a></li>
                <li><a href="{{ route('legal.privacy') }}" class="flex items-center justify-between gap-3 p-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Privacy <x-ui.icon name="chevron-right" :size="16" class="text-muted" /></a></li>
                <li><a href="{{ route('legal.terms') }}" class="flex items-center justify-between gap-3 p-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Terms of sale <x-ui.icon name="chevron-right" :size="16" class="text-muted" /></a></li>
                <li><a href="{{ route('legal.returns') }}" class="flex items-center justify-between gap-3 p-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Returns and refunds <x-ui.icon name="chevron-right" :size="16" class="text-muted" /></a></li>
                <li><a href="{{ route('legal.contact') }}" class="flex items-center justify-between gap-3 p-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Contact support <x-ui.icon name="chevron-right" :size="16" class="text-muted" /></a></li>
                <li><a href="{{ route('shop.search') }}" class="flex items-center justify-between gap-3 p-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Search products <x-ui.icon name="chevron-right" :size="16" class="text-muted" /></a></li>
            </ul>

            <div class="mt-4"><x-ui.button href="{{ route('shop.index') }}" variant="outline">Back to shopping</x-ui.button></div>
        </div>
    </div>
</x-layouts.public>
