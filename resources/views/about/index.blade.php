<x-layouts.public
    title="About"
    description="Company information, service updates and practical notes about data, shopping and payments."
    :canonical="route('about.index')"
>
    <section class="bg-brand-navy text-white">
        <div class="container-page py-12 sm:py-16">
            <p class="inline-flex items-center gap-2 rounded-control border border-white/25 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-brand-yellow">
                <span class="inline-block h-2 w-2 rounded-control bg-brand-yellow" aria-hidden="true"></span>
                About
            </p>

            <h1 class="mt-5 max-w-2xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                Company information and updates.
            </h1>

            <p class="mt-4 max-w-2xl text-base leading-relaxed text-nav-muted">
                Who we are, how orders work and how payments are handled.
            </p>
        </div>
    </section>

    <section class="container-page py-10 sm:py-12" aria-labelledby="post-list-heading">
        <h2 id="post-list-heading" class="sr-only">Company information</h2>

        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <article class="flex flex-col rounded-card border border-line bg-surface p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-yellow-strong">{{ $post['category'] }}</p>

                    <h3 class="mt-2 text-xl font-extrabold leading-snug text-brand-navy">
                        <a href="{{ route('about.show', $post['slug']) }}">{{ $post['title'] }}</a>
                    </h3>

                    <p class="mt-2 flex-1 text-sm leading-relaxed text-muted">{{ $post['excerpt'] }}</p>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-muted">
                        <span>
                            {{ \Illuminate\Support\Carbon::parse($post['date'])->format('j M Y') }}
                            &middot; {{ $post['reading_time'] }} min read
                        </span>
                        <a href="{{ route('about.show', $post['slug']) }}" class="font-bold text-brand-navy underline decoration-brand-yellow decoration-2 underline-offset-4">
                            Read more
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.public>
