<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Design system</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-full bg-surface-muted">
    <main class="container-page py-10">
        <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Design system</h1>
        <p class="mt-2 text-sm text-muted">Datatem tokens and primitives. Preview only, never production data.</p>

        <section class="mt-8">
            <h2 class="text-xl font-extrabold text-brand-navy">Colour tokens</h2>
            <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ([
                    ['Brand navy', 'bg-brand-navy', 'text-white'],
                    ['Brand yellow', 'bg-brand-yellow', 'text-brand-navy'],
                    ['Brand red', 'bg-brand-red', 'text-white'],
                    ['Red strong', 'bg-brand-red-strong', 'text-white'],
                    ['Surface muted', 'bg-surface-muted', 'text-brand-navy'],
                    ['Line', 'bg-line', 'text-brand-navy'],
                ] as [$label, $bg, $fg])
                    <li class="{{ $bg }} {{ $fg }} rounded-card border border-line p-4 text-sm font-bold">{{ $label }}<span class="mt-2 block text-xs font-normal opacity-80">{{ $bg }}</span></li>
                @endforeach
            </ul>
        </section>

        <section class="mt-10">
            <h2 class="text-xl font-extrabold text-brand-navy">Typography</h2>
            <div class="mt-4 rounded-card border border-line bg-surface p-5">
                <p class="text-4xl font-extrabold tracking-tight text-brand-navy">Display 40 / 800</p>
                <p class="mt-3 text-2xl font-extrabold tracking-tight text-brand-navy">Heading 24 / 800</p>
                <p class="mt-3 text-base text-ink">Body 16 / 400. Instrument Sans, 1.5 line height.</p>
                <p class="mt-2 text-sm text-muted">Small 14 / 400. Secondary text on white measures 6.15:1.</p>
            </div>
        </section>
    </main>
</body>
</html>
