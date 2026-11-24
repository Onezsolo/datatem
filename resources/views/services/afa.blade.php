<x-layouts.shop title="AFA registration">
    <div class="border-b border-line bg-surface">
        <div class="container-page flex flex-wrap items-center justify-between gap-3 py-3">
            <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-brand-navy">
                <x-ui.icon name="chevron-left" :size="16" />
                All services
            </a>
            <a href="{{ route('support') }}" class="text-sm font-semibold text-brand-navy underline underline-offset-4">Need help with a registration?</a>
        </div>
    </div>

    <div class="bg-surface-muted pb-10">
        <div class="container-page pt-8 sm:pt-10">
            <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-control bg-brand-navy text-sm font-extrabold tracking-wide text-brand-yellow">AFA</span>
                        <p class="text-sm font-bold text-brand-navy">Farmer registration</p>
                    </div>

                    <h1 class="mt-4 max-w-2xl text-3xl font-extrabold leading-tight tracking-tight text-brand-navy sm:text-4xl">
                        Register a farmer for AFA without the back-and-forth.
                    </h1>
                    <p class="mt-3 max-w-xl text-base leading-relaxed text-muted">
                        Send the farmer's Ghana Card details once. We submit the registration, keep the reference in your service orders and tell you if anything needs correcting.
                    </p>

                    <dl class="mt-7 grid grid-cols-2 gap-x-6 gap-y-4 sm:max-w-md">
                        <div>
                            <dt class="text-sm text-muted">Registration fee</dt>
                            <dd class="mt-0.5 text-xl font-extrabold text-brand-navy">{{ \App\Services\TemplateProductService::currency() }} {{ number_format($price, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted">Usually completed</dt>
                            <dd class="mt-0.5 text-xl font-extrabold text-brand-navy">Same working day</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted">You receive</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-brand-navy">A registration reference</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted">If it fails</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-brand-navy">Wallet refunded automatically</dd>
                        </div>
                    </dl>
                </div>

                <aside class="rounded-card border border-line bg-surface p-5">
                    <h2 class="text-base font-bold text-brand-navy">Before you start</h2>
                    <p class="mt-1 text-sm text-muted">Have these ready. Incomplete details are the main reason registrations get returned.</p>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li class="flex gap-3">
                            <x-ui.icon name="check" :size="16" class="mt-0.5 shrink-0 text-brand-navy" />
                            <span><span class="font-semibold text-brand-navy">The farmer's Ghana Card</span><span class="block text-muted">Name exactly as printed, plus the card number.</span></span>
                        </li>
                        <li class="flex gap-3">
                            <x-ui.icon name="check" :size="16" class="mt-0.5 shrink-0 text-brand-navy" />
                            <span><span class="font-semibold text-brand-navy">An MTN number in the farmer's name</span><span class="block text-muted">Used for AFA messages about the registration.</span></span>
                        </li>
                        <li class="flex gap-3">
                            <x-ui.icon name="check" :size="16" class="mt-0.5 shrink-0 text-brand-navy" />
                            <span><span class="font-semibold text-brand-navy">The town or community</span><span class="block text-muted">As the farmer would say it, not the district office.</span></span>
                        </li>
                    </ul>
                    <a href="#afa-form" class="mt-5 inline-flex min-h-10 w-full items-center justify-center rounded-control bg-brand-navy px-4 text-sm font-bold text-white hover:bg-brand-navy-soft">Go to the form</a>
                </aside>
            </div>

            <div id="afa-form" class="mt-10 grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
                @if (! $available)
                    <div class="rounded-card border border-line bg-surface p-6 lg:col-span-2">
                        <h2 class="text-base font-bold text-brand-navy">AFA registration is closed right now</h2>
                        <p class="mt-1 text-sm text-muted">The developer or service provider has paused new registrations. Check back later or contact support.</p>
                        <a href="{{ route('services.index') }}" class="mt-3 inline-block text-sm font-bold text-brand-navy underline underline-offset-4">See other services</a>
                    </div>
                @else
                    <form action="{{ route('services.afa.store') }}" method="POST" class="rounded-card border border-line bg-surface p-5 sm:p-6">
                        @csrf
                        <h2 class="text-lg font-extrabold text-brand-navy">Farmer details</h2>
                        <p class="mt-1 text-sm text-muted">Match the Ghana Card exactly. We use the phone number to send updates.</p>

                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="full_name" class="block text-sm font-semibold text-brand-navy">Full name</label>
                                <input id="full_name" name="full_name" type="text" required autocomplete="name" value="{{ old('full_name') }}" placeholder="Ama Mensah" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                <p class="mt-1 text-xs text-muted">First name, other names and surname. No nicknames.</p>
                                @error('full_name')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </div>

                            <div class="sm:col-span-2">
                                <div class="rounded-control border border-line bg-surface-muted p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <label for="ghana_card" class="block text-sm font-semibold text-brand-navy">Ghana Card number</label>
                                            <p class="mt-0.5 text-xs text-muted">It starts with GHA and appears on the front of the card.</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-control bg-brand-navy px-3 py-2 font-mono text-sm font-bold tracking-wide text-brand-yellow">GHA-123456789-0</span>
                                    </div>
                                    <input id="ghana_card" name="ghana_card" type="text" required value="{{ old('ghana_card') }}" placeholder="GHA-123456789-0" class="mt-3 h-11 w-full rounded-control border border-line bg-surface px-3 font-mono text-sm tracking-wide">
                                    @error('ghana_card')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-semibold text-brand-navy">MTN phone number</label>
                                <input id="phone" name="phone" type="tel" required autocomplete="tel" value="{{ old('phone') }}" placeholder="024 400 0000" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                <p class="mt-1 text-xs text-muted">The number registered in the farmer's name.</p>
                                @error('phone')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="town" class="block text-sm font-semibold text-brand-navy">Town or community</label>
                                <input id="town" name="town" type="text" required value="{{ old('town') }}" placeholder="Ejisu" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                @error('town')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-muted">Wallet balance</p>
                                <p class="text-lg font-extrabold text-brand-navy">{{ \App\Services\TemplateProductService::currency() }} {{ number_format($wallet, 2) }}</p>
                            </div>
                            <button type="submit" class="inline-flex min-h-12 items-center justify-center rounded-control bg-brand-yellow px-6 text-sm font-extrabold text-brand-navy hover:bg-brand-yellow-strong">
                                Pay {{ \App\Services\TemplateProductService::currency() }} {{ number_format($price, 2) }} and register
                            </button>
                        </div>
                        @error('wallet')<p class="mt-2 text-sm font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                        @error('service')<p class="mt-2 text-sm font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </form>

                    <div class="space-y-4">
                        <section class="rounded-card border border-line bg-surface p-5">
                            <h2 class="text-base font-bold text-brand-navy">What happens after you submit</h2>
                            <ol class="mt-3 space-y-4">
                                <li class="flex gap-3">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-control bg-brand-navy text-xs font-bold text-brand-yellow">1</span>
                                    <div>
                                        <p class="text-sm font-semibold text-brand-navy">We check the details</p>
                                        <p class="mt-0.5 text-sm text-muted">If the Ghana Card name and number do not match, we flag it before submitting so it is not returned.</p>
                                    </div>
                                </li>
                                <li class="flex gap-3">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-control bg-brand-navy text-xs font-bold text-brand-yellow">2</span>
                                    <div>
                                        <p class="text-sm font-semibold text-brand-navy">The registration is sent to AFA</p>
                                        <p class="mt-0.5 text-sm text-muted">This usually happens the same working day, Monday to Friday.</p>
                                    </div>
                                </li>
                                <li class="flex gap-3">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-control bg-brand-navy text-xs font-bold text-brand-yellow">3</span>
                                    <div>
                                        <p class="text-sm font-semibold text-brand-navy">You get a reference</p>
                                        <p class="mt-0.5 text-sm text-muted">The reference appears in Service orders and is what you quote when following up.</p>
                                    </div>
                                </li>
                            </ol>
                        </section>

                        <section class="rounded-card border border-line bg-surface p-5">
                            <h2 class="text-base font-bold text-brand-navy">Questions we get</h2>
                            <div class="mt-2 divide-y divide-line">
                                <details class="group py-3">
                                    <summary class="cursor-pointer list-none text-sm font-semibold text-brand-navy marker:hidden">What if the farmer has no MTN number?</summary>
                                    <p class="mt-2 text-sm leading-relaxed text-muted">AFA sends updates by MTN number. If the farmer uses another network, get an MTN number in their name before starting; we cannot complete the registration without it.</p>
                                </details>
                                <details class="group py-3">
                                    <summary class="cursor-pointer list-none text-sm font-semibold text-brand-navy marker:hidden">Can I correct a detail after paying?</summary>
                                    <p class="mt-2 text-sm leading-relaxed text-muted">Yes. If the registration has not been submitted yet, contact support with the reference and the correction. After submission, changes go through AFA directly.</p>
                                </details>
                                <details class="group py-3">
                                    <summary class="cursor-pointer list-none text-sm font-semibold text-brand-navy marker:hidden">When is the fee refunded?</summary>
                                    <p class="mt-2 text-sm leading-relaxed text-muted">If we cannot submit the registration or AFA rejects it for a reason on our side, the fee returns to your wallet automatically.</p>
                                </details>
                            </div>
                            <a href="{{ route('support') }}" class="mt-2 inline-block text-sm font-bold text-brand-navy underline underline-offset-4">Talk to support</a>
                        </section>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.shop>
