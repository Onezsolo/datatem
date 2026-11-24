@guest
    <div class="rounded-card border border-line bg-surface p-4 text-sm font-semibold text-brand-navy">
        Sign in to purchase from your wallet.
        <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="underline underline-offset-4">Sign in or create an account</a>
    </div>
@endguest
