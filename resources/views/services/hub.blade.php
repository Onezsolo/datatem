<x-layouts.shop title="Services">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Services</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Data and digital services</h1>
                <p class="mt-1 text-sm text-muted">Fulfilled through the PATUPAA API and paid from your wallet. Items disabled at PATUPAA stay disabled here.</p>
            </div>
            <a href="{{ route('services.orders') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Service orders</a>
        </header>

        @include('partials.guest-purchase-note')

        @php
            $cards = [
                ['label' => 'Data bundles', 'desc' => 'MTN, AirtelTigo and Telecel bundles.', 'route' => 'services.data', 'type' => 'data_bundle', 'count' => $counts['data_bundle'] ?? 0, 'icon' => 'phone'],
                ['label' => 'WAEC PINs', 'desc' => 'Results checker PINs for WASSCE.', 'route' => 'services.waec', 'type' => 'waec', 'count' => $counts['waec'] ?? 0, 'icon' => 'check'],
                ['label' => 'Utility bills', 'desc' => 'Prepaid electricity and bill payments.', 'route' => 'services.utility', 'type' => 'utility', 'count' => $counts['utility'] ?? 0, 'icon' => 'grid'],
                ['label' => 'Subscriptions', 'desc' => 'Gift cards and shared account plans.', 'route' => 'services.subscriptions', 'type' => 'digital', 'count' => $counts['digital'] ?? 0, 'icon' => 'bag'],
            ];
        @endphp

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $card)
                @php($available = ($toggles[$card['type']] ?? false) && $card['count'] > 0)
                <x-ui.card :href="$available ? route($card['route']) : null" padding="md" class="h-full {{ $available ? 'hover:bg-surface-muted' : 'opacity-60' }}">
                    <div class="flex items-start justify-between gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-control bg-brand-navy text-brand-yellow">
                            <x-ui.icon :name="$card['icon']" :size="18" />
                        </span>
                        <x-ui.badge :variant="$available ? 'accent' : 'neutral'" size="sm">{{ $available ? $card['count'].' available' : 'Off' }}</x-ui.badge>
                    </div>
                    <p class="mt-3 text-sm font-bold text-brand-navy">{{ $card['label'] }}</p>
                    <p class="mt-1 text-xs leading-relaxed text-muted">{{ $card['desc'] }}</p>
                </x-ui.card>
            @endforeach

            @php($afaAvailable = ($toggles['afa'] ?? false) && $afaPrice > 0)
            <x-ui.card :href="$afaAvailable ? route('services.afa') : null" padding="md" class="h-full {{ $afaAvailable ? 'hover:bg-surface-muted' : 'opacity-60' }}">
                <div class="flex items-start justify-between gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-control bg-brand-navy text-brand-yellow">
                        <x-ui.icon name="help" :size="18" />
                    </span>
                    <x-ui.badge :variant="$afaAvailable ? 'accent' : 'neutral'" size="sm">{{ $afaAvailable ? 'GHS '.number_format($afaPrice, 2) : 'Off' }}</x-ui.badge>
                </div>
                <p class="mt-3 text-sm font-bold text-brand-navy">AFA registration</p>
                <p class="mt-1 text-xs leading-relaxed text-muted">Register a farmer for AFA with Ghana Card details.</p>
            </x-ui.card>
        </div>
    </div>
</x-layouts.shop>
