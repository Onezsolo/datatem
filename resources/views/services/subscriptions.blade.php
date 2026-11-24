<x-layouts.shop title="Subscriptions">
    <div class="space-y-6">
        <div>
            <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-brand-navy">
                <x-ui.icon name="chevron-left" :size="16" />
                All services
            </a>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-brand-navy">Subscriptions</h1>
                    <p class="mt-1 text-sm text-muted">Gift cards and shared plans.</p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-muted">Wallet <span class="font-bold text-brand-navy">GHS {{ number_format($wallet, 2) }}</span></span>
                    <a href="{{ route('topup') }}" class="font-bold text-brand-navy underline underline-offset-4">Top up</a>
                </div>
            </div>
        </div>

        @include('partials.guest-purchase-note')

        @if ($items->isEmpty())
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No subscriptions available right now.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($items as $item)
                    @php($gift = is_array($item->meta['gift_card'] ?? null) ? $item->meta['gift_card'] : null)
                    <div class="rounded-card border border-line bg-surface p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-brand-navy">{{ $item->name }}</p>
                                <p class="mt-0.5 text-xs text-muted">
                                    GHS {{ number_format((float) $item->price, 2) }}
                                    @if ($gift && !empty($gift['region_code']))
                                        · region {{ $gift['region_name'] ?? $gift['region_code'] }}
                                    @endif
                                </p>
                            </div>
                            <x-ui.badge variant="neutral" size="sm">{{ $gift ? 'Gift card' : 'Shared account' }}</x-ui.badge>
                        </div>

                        <form action="{{ route('services.subscriptions.store', $item) }}" method="POST" class="mt-3 space-y-2">
                            @csrf
                                    <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <input name="recipient_email" type="email" required placeholder="Recipient email" class="h-11 w-full rounded-control border border-line px-3 text-sm">
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
                            <x-ui.button type="submit" size="sm" class="w-full">Buy subscription</x-ui.button>
                        </form>
                        @error('wallet')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                        @error('region_confirmed')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                        @error('terms_accepted')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
