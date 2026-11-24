<x-layouts.shop title="AFA registration">
    <div class="space-y-6">
        <header>
            <p class="text-xs font-bold uppercase tracking-wide text-muted">Services / AFA</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">AFA registration</h1>
            <p class="mt-1 text-sm text-muted">Register a farmer using Ghana Card details. Submissions are processed through the PATUPAA API.</p>
        </header>

        @include('partials.guest-purchase-note')

        @if (! $available)
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">AFA registration is unavailable.</p>
                <p class="mt-1 text-sm text-muted">The admin must enable the service and set its price.</p>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
                <form action="{{ route('services.afa.store') }}" method="POST" class="rounded-card border border-line bg-surface p-5">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="full_name" class="block text-sm font-bold text-brand-navy">Full name</label>
                            <input id="full_name" name="full_name" type="text" required placeholder="As shown on the Ghana Card" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-bold text-brand-navy">MTN phone</label>
                            <input id="phone" name="phone" type="tel" required placeholder="0244000000" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div>
                            <label for="ghana_card" class="block text-sm font-bold text-brand-navy">Ghana Card number</label>
                            <input id="ghana_card" name="ghana_card" type="text" required placeholder="GHA-XXXXXXXXX-X" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="town" class="block text-sm font-bold text-brand-navy">Town</label>
                            <input id="town" name="town" type="text" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        </div>
                    </div>
                    @error('wallet')<p class="mt-2 text-sm font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    <div class="mt-5">
                        <x-ui.button type="submit" size="lg" class="w-full">Pay GHS {{ number_format($price, 2) }} and submit</x-ui.button>
                    </div>
                </form>

                <aside class="h-fit rounded-card border border-line bg-surface p-5 text-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">Fee</p>
                    <p class="mt-1 text-lg font-extrabold text-brand-navy">GHS {{ number_format($price, 2) }}</p>
                    <p class="mt-3 text-xs leading-relaxed text-muted">Charged from your wallet before the registration is submitted. Failed submissions are refunded automatically.</p>
                    <a href="{{ route('services.orders') }}" class="mt-4 inline-block text-sm font-bold text-brand-navy underline underline-offset-4">View service orders</a>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.shop>
