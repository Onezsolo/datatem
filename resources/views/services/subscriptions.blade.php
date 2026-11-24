<x-layouts.shop title="Subscriptions">
    <div class="space-y-6">
        <x-ui.page-heading title="Subscriptions" subtitle="Gift cards and shared plans." :back-url="route('services.index')">
            <x-slot:actions>
                <span class="text-muted">Wallet <span class="font-bold text-brand-navy">GHS {{ number_format($wallet, 2) }}</span></span>
                <a href="{{ route('topup') }}" class="font-bold text-brand-navy underline underline-offset-4">Top up</a>
            </x-slot:actions>
        </x-ui.page-heading>
@include('partials.guest-purchase-note')

        @if ($items->isEmpty())
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No subscriptions available right now.</p>
            </div>
        @else
            <div x-data="{ active: {{ $items->first()->id }} }">
                <div class="flex gap-2 overflow-x-auto border-b border-line pb-3" role="tablist" aria-label="Subscription products">
                    @foreach ($items as $item)
                        <button
                            type="button"
                            role="tab"
                            @click="active = {{ $item->id }}"
                            :aria-selected="active === {{ $item->id }}"
                            class="service-tab"
                        >
                            {{ $item->name }}
                            <span class="text-xs font-semibold opacity-70">GHS {{ number_format((float) $item->price, 2) }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($items as $item)
                    @php($gift = is_array($item->meta['gift_card'] ?? null) ? $item->meta['gift_card'] : null)
                    <div x-show="active === {{ $item->id }}" x-cloak class="mt-4 max-w-2xl" role="tabpanel" aria-label="{{ $item->name }}">
                        <div class="rounded-card border border-line bg-surface p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h2 class="text-base font-extrabold text-brand-navy">{{ $item->name }}</h2>
                                    <p class="mt-0.5 text-sm text-muted">
                                        GHS {{ number_format((float) $item->price, 2) }}
                                        @if ($gift && !empty($gift['region_code']))
                                            · region {{ $gift['region_name'] ?? $gift['region_code'] }}
                                        @endif
                                    </p>
                                </div>
                                <x-ui.badge variant="neutral" size="sm">{{ $gift ? 'Gift card' : 'Shared account' }}</x-ui.badge>
                            </div>

                            <form action="{{ route('services.subscriptions.store', $item) }}" method="POST" class="mt-4 space-y-3">
                                @csrf
                                <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

                                <div>
                                    <label for="email-{{ $item->id }}" class="block text-sm font-semibold text-brand-navy">Recipient email</label>
                                    <input id="email-{{ $item->id }}" name="recipient_email" type="email" required placeholder="name@example.com" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                </div>

                                @if ($gift && !empty($gift['region_code']))
                                    <label class="flex items-start gap-2 text-xs text-muted">
                                        <input type="checkbox" name="region_confirmed" value="1" required class="mt-0.5 h-4 w-4 rounded-control border-line">
                                        I confirm my account region matches {{ $gift['region_name'] ?? $gift['region_code'] }}.
                                    </label>
                                    <label class="flex items-start gap-2 text-xs text-muted">
                                        <input type="checkbox" name="terms_accepted" value="1" required class="mt-0.5 h-4 w-4 rounded-control border-line">
                                        I accept the gift card redemption terms.
                                    </label>
                                @endif

                                <button type="submit" class="min-h-11 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Buy subscription</button>
                                @error('wallet')<p class="text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                                @error('region_confirmed')<p class="text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                                @error('terms_accepted')<p class="text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
