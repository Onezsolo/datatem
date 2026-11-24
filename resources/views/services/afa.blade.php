<x-layouts.shop title="AFA registration">
    <div class="bg-surface-muted py-8">
        <div class="container-page">
            <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-brand-navy">
                <x-ui.icon name="chevron-left" :size="16" />
                All services
            </a>

            <div class="mt-4 grid gap-6 lg:grid-cols-[1fr_20rem] lg:items-start">
                @if (! $available)
                    <div class="rounded-card border border-line bg-surface p-6 lg:col-span-2">
                        <h1 class="text-lg font-extrabold text-brand-navy">AFA registration</h1>
                        <p class="mt-1 text-sm text-muted">Registration is paused right now. Check back later or contact support.</p>
                        <a href="{{ route('services.index') }}" class="mt-3 inline-block text-sm font-bold text-brand-navy underline underline-offset-4">See other services</a>
                    </div>
                @else
                    <form action="{{ route('services.afa.store') }}" method="POST" class="rounded-card border border-line bg-surface p-5 sm:p-6">
                        @csrf
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-control bg-brand-navy text-xs font-extrabold tracking-wide text-brand-yellow">AFA</span>
                            <div>
                                <h1 class="text-xl font-extrabold text-brand-navy">AFA registration</h1>
                                <p class="text-sm text-muted">Enter the farmer's Ghana Card details.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="full_name" class="block text-sm font-semibold text-brand-navy">Full name</label>
                                <input id="full_name" name="full_name" type="text" required autocomplete="name" value="{{ old('full_name') }}" placeholder="Ama Mensah" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                @error('full_name')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="ghana_card" class="block text-sm font-semibold text-brand-navy">Ghana Card number</label>
                                <input id="ghana_card" name="ghana_card" type="text" required value="{{ old('ghana_card') }}" placeholder="GHA-123456789-0" class="mt-1 h-11 w-full rounded-control border border-line px-3 font-mono text-sm tracking-wide">
                                <p class="mt-1 text-xs text-muted">On the front of the card, format GHA-XXXXXXXXX-X.</p>
                                @error('ghana_card')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-semibold text-brand-navy">MTN phone number</label>
                                <input id="phone" name="phone" type="tel" required autocomplete="tel" value="{{ old('phone') }}" placeholder="0244000000" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                @error('phone')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="town" class="block text-sm font-semibold text-brand-navy">Town or community</label>
                                <input id="town" name="town" type="text" required value="{{ old('town') }}" placeholder="Ejisu" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                @error('town')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-muted">Wallet <span class="font-bold text-brand-navy">{{ \App\Services\TemplateProductService::currency() }} {{ number_format($wallet, 2) }}</span></p>
                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-control bg-brand-yellow px-5 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">
                                Pay {{ \App\Services\TemplateProductService::currency() }} {{ number_format($price, 2) }}
                            </button>
                        </div>
                        @error('wallet')<p class="mt-2 text-sm font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                        @error('service')<p class="mt-2 text-sm font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </form>

                    <aside class="rounded-card border border-line bg-surface p-5 text-sm">
                        <p class="text-muted">Registration fee</p>
                        <p class="mt-1 text-2xl font-extrabold text-brand-navy">{{ \App\Services\TemplateProductService::currency() }} {{ number_format($price, 2) }}</p>
                        <p class="mt-3 text-xs leading-relaxed text-muted">Paid from your wallet. If the registration cannot be submitted, the fee is refunded automatically.</p>
                        <p class="mt-3 text-xs leading-relaxed text-muted">The registration reference appears in Service orders.</p>
                        <a href="{{ route('support') }}" class="mt-4 inline-block text-sm font-bold text-brand-navy underline underline-offset-4">Contact support</a>
                    </aside>
                @endif
            </div>
        </div>
    </div>
</x-layouts.shop>
