@php
    $published = \Illuminate\Support\Carbon::parse($post['date']);
    $brandName = \App\Support\Brand::name();
    $postUrl = route('about.show', $post['slug']);
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Article',
                'headline' => $post['title'],
                'description' => $post['excerpt'],
                'datePublished' => $published->toAtomString(),
                'dateModified' => $published->toAtomString(),
                'url' => $postUrl,
                'mainEntityOfPage' => $postUrl,
                'author' => [
                    '@type' => 'Organization',
                    'name' => $brandName,
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => $brandName,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => \App\Support\Brand::logoUrl(),
                    ],
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'About', 'item' => route('about.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $post['title'], 'item' => $postUrl],
                ],
            ],
        ],
    ];
@endphp

<x-layouts.public
    :title="$post['title']"
    :description="$post['excerpt']"
    :canonical="$postUrl"
    :schema="$schema"
    type="article"
>
    <article class="container-page max-w-3xl py-10 sm:py-14">
        <a href="{{ route('about.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-brand-navy">
            <span aria-hidden="true">&larr;</span>
            Back to company information
        </a>

        <div class="mt-6 flex flex-wrap items-center gap-2 text-xs font-bold uppercase tracking-wide text-brand-yellow-strong">
            <span>{{ $post['category'] }}</span>
            <span aria-hidden="true">&middot;</span>
            <time datetime="{{ $published->toDateString() }}">{{ $published->format('j M Y') }}</time>
            <span aria-hidden="true">&middot;</span>
            <span>{{ $post['reading_time'] }} min read</span>
        </div>

        <h1 class="mt-3 text-3xl font-extrabold leading-tight text-brand-navy sm:text-4xl">{{ $post['title'] }}</h1>

        <p class="mt-4 text-base leading-relaxed text-muted">{{ $post['excerpt'] }}</p>

        <div class="article-body mt-8">
            @include($contentView)
        </div>

        <div class="mt-10 rounded-card border border-line bg-surface-muted p-6">
            <h2 class="text-lg font-extrabold text-brand-navy">Ready to get started?</h2>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                Create a free account, add money to your wallet and keep your data, shopping and payments in one place.
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-ui.button href="{{ route('signup') }}">Create free account</x-ui.button>
                <x-ui.button href="{{ route('login') }}" variant="ghost">Sign in</x-ui.button>
            </div>
        </div>
    </article>
</x-layouts.public>
