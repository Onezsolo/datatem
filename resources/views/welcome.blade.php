@extends('layouts.app')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<style>
:root {
    --landing-bg: #0a0e17;
    --landing-surface: #111827;
    --landing-card: #1a2332;
    --landing-border: #2a3548;
    --landing-fg: #f0f4f8;
    --landing-muted: #8896ab;
    --landing-accent: #00d4aa;
    --landing-accent-dark: #00a888;
    --landing-highlight: #0ef5cc;
}
.landing-page { font-family: 'DM Sans', sans-serif; background-color: var(--landing-bg); color: var(--landing-fg); overflow-x: hidden; }
.font-display { font-family: 'Space Grotesk', sans-serif; }
.grid-pattern {
    background-image: linear-gradient(rgba(42, 53, 72, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(42, 53, 72, 0.3) 1px, transparent 1px);
    background-size: 60px 60px;
}
.orb { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.4; animation: float 20s ease-in-out infinite; }
.orb-1 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(0, 212, 170, 0.5) 0%, transparent 70%); top: -200px; right: -200px; animation-delay: 0s; }
.orb-2 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(0, 168, 136, 0.4) 0%, transparent 70%); bottom: 20%; left: -100px; animation-delay: -7s; }
.orb-3 { width: 300px; height: 300px; background: radial-gradient(circle, rgba(14, 245, 204, 0.3) 0%, transparent 70%); top: 50%; right: 10%; animation-delay: -14s; }
@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    25% { transform: translate(30px, -30px) scale(1.05); }
    50% { transform: translate(-20px, 20px) scale(0.95); }
    75% { transform: translate(20px, 10px) scale(1.02); }
}
.data-stream { position: absolute; width: 2px; height: 100%; top: 0; overflow: hidden; }
.data-stream::before {
    content: ''; position: absolute; width: 100%; height: 60px;
    background: linear-gradient(to bottom, transparent, var(--landing-accent), transparent);
    animation: stream 3s linear infinite;
}
@keyframes stream {
    0% { top: -60px; opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { top: 100%; opacity: 0; }
}
.reveal { opacity: 0; transform: translateY(40px); transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-delay-1 { transition-delay: 0.1s; } .reveal-delay-2 { transition-delay: 0.2s; } .reveal-delay-3 { transition-delay: 0.3s; } .reveal-delay-4 { transition-delay: 0.4s; }
.feature-card {
    background: linear-gradient(135deg, rgba(26, 35, 50, 0.8) 0%, rgba(17, 24, 39, 0.6) 100%);
    border: 1px solid var(--landing-border);
    backdrop-filter: blur(10px);
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.feature-card:hover {
    border-color: var(--landing-accent);
    transform: translateY(-8px);
    box-shadow: 0 20px 40px -20px rgba(0, 212, 170, 0.3);
}
.feature-card:hover .card-icon { transform: scale(1.1); background: var(--landing-accent); color: var(--landing-bg); }
.card-icon { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.btn-primary {
    background: linear-gradient(135deg, var(--landing-accent) 0%, var(--landing-accent-dark) 100%);
    color: var(--landing-bg);
    font-weight: 600;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}
.btn-primary::before {
    content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s ease;
}
.btn-primary:hover::before { left: 100%; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 30px -10px rgba(0, 212, 170, 0.5); }
.btn-primary:active { transform: translateY(0); }
.btn-secondary {
    background: transparent;
    border: 1px solid var(--landing-border);
    color: var(--landing-fg);
    transition: all 0.3s ease;
}
.btn-secondary:hover { border-color: var(--landing-accent); color: var(--landing-accent); background: rgba(0, 212, 170, 0.05); }
.stat-value {
    background: linear-gradient(135deg, var(--landing-fg) 0%, var(--landing-accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.pricing-card {
    background: linear-gradient(180deg, rgba(26, 35, 50, 0.9) 0%, rgba(17, 24, 39, 0.7) 100%);
    border: 1px solid var(--landing-border);
    transition: all 0.4s ease;
}
.pricing-card:hover { border-color: rgba(0, 212, 170, 0.5); }
.pricing-card.featured {
    border-color: var(--landing-accent);
    background: linear-gradient(180deg, rgba(0, 212, 170, 0.1) 0%, rgba(17, 24, 39, 0.9) 100%);
}
.pulse { animation: pulse 2s ease-in-out infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
</style>
<div class="landing-page min-h-screen relative" x-data="{ activeNet: 'all' }">

    <!-- Background (same as sample) -->
    <div class="fixed inset-0 grid-pattern pointer-events-none z-0"></div>
    <div class="orb orb-1 pointer-events-none z-0"></div>
    <div class="orb orb-2 pointer-events-none z-0"></div>
    <div class="orb orb-3 pointer-events-none z-0"></div>
    <div class="data-stream left-[10%] z-0" style="animation-delay: 0s;"></div>
    <div class="data-stream left-[25%] z-0" style="animation-delay: 0.5s;"></div>
    <div class="data-stream left-[75%] z-0" style="animation-delay: 1s;"></div>
    <div class="data-stream left-[90%] z-0" style="animation-delay: 1.5s;"></div>

    <!-- Hero (sample structure) -->
    <section class="relative min-h-screen flex items-center pt-20 z-10">
        <div class="max-w-7xl mx-auto px-6 py-20 w-full">
            <div class="max-w-4xl">
                <div class="reveal flex items-center gap-3 mb-6">
                    <span class="w-2 h-2 rounded-full bg-[#00d4aa] pulse"></span>
                    <span class="text-[#8896ab] font-medium text-sm tracking-wide">LIVE · GHANA · MTN · AIRTELTIGO · TELECEL</span>
                </div>
                <h1 class="reveal reveal-delay-1 font-display text-5xl sm:text-6xl lg:text-7xl font-bold leading-[1.1] mb-6">
                    Data in
                    <span class="block text-[#00d4aa]">Seconds.</span>
                </h1>
                <p class="reveal reveal-delay-2 text-[#8896ab] text-lg sm:text-xl max-w-2xl mb-10 leading-relaxed">
                    MTN, AirtelTigo & Telecel bundles delivered to your phone in seconds. No queues, no hassle. Top up your number and stay connected.
                </p>
                <div class="reveal reveal-delay-3 flex flex-wrap gap-4 mb-16">
                    <a href="/bundles" class="btn-primary px-8 py-4 rounded-xl font-display font-semibold text-base flex items-center gap-3">
                        Browse bundles
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                    <a href="/support" class="btn-secondary px-8 py-4 rounded-xl font-display font-semibold text-base flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        How it works
                    </a>
                </div>
                <div class="reveal reveal-delay-4 grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-10">
                    <div>
                        <div class="stat-value font-display text-3xl sm:text-4xl font-bold">Instant</div>
                        <div class="text-[#8896ab] text-sm mt-1">Delivery</div>
                    </div>
                    <div>
                        <div class="stat-value font-display text-3xl sm:text-4xl font-bold">40%</div>
                        <div class="text-[#8896ab] text-sm mt-1">Savings</div>
                    </div>
                    <div>
                        <div class="stat-value font-display text-3xl sm:text-4xl font-bold">100%</div>
                        <div class="text-[#8896ab] text-sm mt-1">Guaranteed</div>
                    </div>
                    <div>
                        <div class="stat-value font-display text-3xl sm:text-4xl font-bold">24/7</div>
                        <div class="text-[#8896ab] text-sm mt-1">Support</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trusted / Benefits strip (sample style) -->
    <section class="relative py-16 border-y border-[#2a3548] bg-[#111827]/30 backdrop-blur-sm z-10">
        <div class="max-w-7xl mx-auto px-6">
            <p class="reveal text-center text-[#8896ab] text-sm font-medium tracking-widest mb-8">TRUSTED BY THOUSANDS IN GHANA</p>
            <div class="reveal reveal-delay-1 flex flex-wrap justify-center items-center gap-8 sm:gap-16 text-[#8896ab]">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#00d4aa]/10 flex items-center justify-center text-[#00d4aa]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <span class="font-display font-semibold">Instant delivery</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#00d4aa]/10 flex items-center justify-center text-[#00d4aa]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="font-display font-semibold">Up to 40% off</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#00d4aa]/10 flex items-center justify-center text-[#00d4aa]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <span class="font-display font-semibold">Money-back guarantee</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Features: Why Choose R2BMarket (sample feature cards) -->
    <section id="features" class="relative py-24 z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="reveal font-display text-4xl sm:text-5xl font-bold mb-4 text-[#f0f4f8]">Why Choose R2BMarket</h2>
                <p class="reveal reveal-delay-1 text-[#8896ab] text-lg max-w-2xl mx-auto">Your connection, simplified. No hidden charges, no hassle.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <article class="reveal feature-card p-8 rounded-2xl">
                    <div class="card-icon w-14 h-14 rounded-xl bg-[#00d4aa]/10 flex items-center justify-center mb-6 text-[#00d4aa]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="font-display text-xl font-semibold mb-3 text-[#f0f4f8]">Instant Delivery</h3>
                    <p class="text-[#8896ab] leading-relaxed">Data hits your number in seconds. No waiting, no queues.</p>
                </article>
                <article class="reveal reveal-delay-1 feature-card p-8 rounded-2xl">
                    <div class="card-icon w-14 h-14 rounded-xl bg-[#00d4aa]/10 flex items-center justify-center mb-6 text-[#00d4aa]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-display text-xl font-semibold mb-3 text-[#f0f4f8]">Real Savings</h3>
                    <p class="text-[#8896ab] leading-relaxed">Up to 40% cheaper than retail. Pay less, get more data.</p>
                </article>
                <article class="reveal reveal-delay-2 feature-card p-8 rounded-2xl">
                    <div class="card-icon w-14 h-14 rounded-xl bg-[#00d4aa]/10 flex items-center justify-center mb-6 text-[#00d4aa]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                    </div>
                    <h3 class="font-display text-xl font-semibold mb-3 text-[#f0f4f8]">100% Guaranteed</h3>
                    <p class="text-[#8896ab] leading-relaxed">Not delivered? Full refund. No questions asked.</p>
                </article>
                <article class="reveal feature-card p-8 rounded-2xl">
                    <div class="card-icon w-14 h-14 rounded-xl bg-[#00d4aa]/10 flex items-center justify-center mb-6 text-[#00d4aa]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75m15.75 0h.75a.75.75 0 01.75.75v.75m0 0H18m-9-1.5v.75M9 15v.75M9 15h.75M15 15h.75"/></svg>
                    </div>
                    <h3 class="font-display text-xl font-semibold mb-3 text-[#f0f4f8]">No Hidden Fees</h3>
                    <p class="text-[#8896ab] leading-relaxed">What you see is what you pay. Transparent pricing always.</p>
                </article>
                <article class="reveal reveal-delay-1 feature-card p-8 rounded-2xl">
                    <div class="card-icon w-14 h-14 rounded-xl bg-[#00d4aa]/10 flex items-center justify-center mb-6 text-[#00d4aa]">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                    </div>
                    <h3 class="font-display text-xl font-semibold mb-3 text-[#f0f4f8]">24/7 Support</h3>
                    <p class="text-[#8896ab] leading-relaxed">We're always here to help with your purchases.</p>
                </article>
                <article class="reveal reveal-delay-2 feature-card p-8 rounded-2xl">
                    <div class="mb-6">
                        <img src="{{ asset('assets/l1.png') }}" alt="" class="h-20 w-auto object-contain">
                    </div>
                    <h3 class="font-display text-xl font-semibold mb-3 text-[#f0f4f8]">All Networks</h3>
                    <p class="text-[#8896ab] leading-relaxed">MTN, AirtelTigo & Telecel. One place for all your data.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- How It Works (sample 3-step style) -->
    <section id="how-it-works" class="relative py-24 bg-[#111827]/20 z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="reveal font-display text-4xl sm:text-5xl font-bold mb-4 text-[#f0f4f8]">How It Works</h2>
                <p class="reveal reveal-delay-1 text-[#8896ab] text-lg max-w-2xl mx-auto">From signup to data in three simple steps.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="reveal relative">
                    <div class="absolute top-12 left-full w-full h-px bg-gradient-to-r from-[#00d4aa] to-transparent hidden md:block"></div>
                    <div class="w-12 h-12 rounded-full bg-[#00d4aa] text-[#0a0e17] font-display font-bold text-xl flex items-center justify-center mb-6">1</div>
                    <h3 class="font-display text-2xl font-semibold mb-3 text-[#f0f4f8]">Choose Your Network</h3>
                    <p class="text-[#8896ab] leading-relaxed">Pick MTN, AirtelTigo, or Telecel and select the bundle you need.</p>
                </div>
                <div class="reveal reveal-delay-1 relative">
                    <div class="absolute top-12 left-full w-full h-px bg-gradient-to-r from-[#00d4aa] to-transparent hidden md:block"></div>
                    <div class="w-12 h-12 rounded-full bg-[#00d4aa] text-[#0a0e17] font-display font-bold text-xl flex items-center justify-center mb-6">2</div>
                    <h3 class="font-display text-2xl font-semibold mb-3 text-[#f0f4f8]">Top Up & Pay</h3>
                    <p class="text-[#8896ab] leading-relaxed">Add funds to your wallet and complete the purchase securely.</p>
                </div>
                <div class="reveal reveal-delay-2">
                    <div class="w-12 h-12 rounded-full bg-[#00d4aa] text-[#0a0e17] font-display font-bold text-xl flex items-center justify-center mb-6">3</div>
                    <h3 class="font-display text-2xl font-semibold mb-3 text-[#f0f4f8]">Get Your Data</h3>
                    <p class="text-[#8896ab] leading-relaxed">Data is delivered to your number instantly. Done.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing / Networks (sample pricing cards) -->
    <section id="pricing" class="relative py-24 z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="reveal font-display text-4xl sm:text-5xl font-bold mb-4 text-[#f0f4f8]">Pick Your Network</h2>
                <p class="reveal reveal-delay-1 text-[#8896ab] text-lg max-w-2xl mx-auto">Sample bundles. Select a provider to see options and buy.</p>
            </div>
            <div class="flex flex-wrap justify-center gap-2 mb-8">
                <button @click="activeNet = 'all'" :class="activeNet === 'all' ? 'bg-[#00d4aa] text-[#0a0e17]' : 'bg-[#1a2332] text-[#8896ab] border border-[#2a3548] hover:border-[#00d4aa]'" class="px-5 py-2.5 rounded-lg text-sm font-display font-semibold transition-all">All</button>
                <button @click="activeNet = 'mtn'" :class="activeNet === 'mtn' ? 'bg-[#FFCC00] text-[#0a0e17]' : 'bg-[#1a2332] text-[#8896ab] border border-[#2a3548] hover:border-[#FFCC00]'" class="px-5 py-2.5 rounded-lg text-sm font-display font-semibold transition-all">MTN</button>
                <button @click="activeNet = 'at'" :class="activeNet === 'at' ? 'bg-[#00d4aa] text-[#0a0e17]' : 'bg-[#1a2332] text-[#8896ab] border border-[#2a3548] hover:border-[#00d4aa]'" class="px-5 py-2.5 rounded-lg text-sm font-display font-semibold transition-all">AirtelTigo</button>
                <button @click="activeNet = 'telecel'" :class="activeNet === 'telecel' ? 'bg-red-500 text-white' : 'bg-[#1a2332] text-[#8896ab] border border-[#2a3548] hover:border-red-500'" class="px-5 py-2.5 rounded-lg text-sm font-display font-semibold transition-all">Telecel</button>
            </div>
            <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
                <!-- MTN -->
                <div x-show="activeNet === 'all' || activeNet === 'mtn'" x-cloak class="reveal pricing-card featured p-8 rounded-2xl relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-[#FFCC00] text-[#0a0e17] text-xs font-bold rounded-full font-display">POPULAR</div>
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ asset('assets/mtn.jpg') }}" alt="MTN" class="h-10 w-auto object-contain">
                        <span class="text-[#8896ab] text-sm font-medium">MTN</span>
                    </div>
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-[#f0f4f8]"><span>5GB</span><span class="font-display font-bold">GH₵ 25</span></div>
                        <div class="flex justify-between text-[#f0f4f8]"><span>10GB</span><span class="font-display font-bold">GH₵ 40</span></div>
                        <div class="flex justify-between text-[#f0f4f8]"><span>20GB</span><span class="font-display font-bold">GH₵ 75</span></div>
                    </div>
                    <a href="#" @click.prevent="localStorage.getItem('token') ? window.location.href='/bundles?network=mtn' : window.location.href='/login'" class="btn-primary w-full py-3 rounded-xl font-display font-semibold text-center block">Buy MTN Data</a>
                </div>
                <!-- AirtelTigo -->
                <div x-show="activeNet === 'all' || activeNet === 'at'" x-cloak class="reveal reveal-delay-1 pricing-card p-8 rounded-2xl">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ asset('assets/airtelTigo.png') }}" alt="AirtelTigo" class="h-10 w-auto object-contain bg-white rounded p-1">
                        <span class="text-[#00d4aa] text-sm font-medium">AirtelTigo</span>
                    </div>
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-[#f0f4f8]"><span>7GB</span><span class="font-display font-bold">GH₵ 30</span></div>
                        <div class="flex justify-between text-[#f0f4f8]"><span>15GB</span><span class="font-display font-bold">GH₵ 55</span></div>
                        <div class="flex justify-between text-[#f0f4f8]"><span>25GB</span><span class="font-display font-bold">GH₵ 85</span></div>
                    </div>
                    <a href="#" @click.prevent="localStorage.getItem('token') ? window.location.href='/bundles?network=at' : window.location.href='/login'" class="btn-primary w-full py-3 rounded-xl font-display font-semibold text-center block">Buy AirtelTigo</a>
                </div>
                <!-- Telecel -->
                <div x-show="activeNet === 'all' || activeNet === 'telecel'" x-cloak class="reveal reveal-delay-2 pricing-card p-8 rounded-2xl">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ asset('assets/telecel.png') }}" alt="Telecel" class="h-10 w-auto object-contain bg-white rounded p-1">
                        <span class="text-red-400 text-sm font-medium">Telecel</span>
                    </div>
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-[#f0f4f8]"><span>10GB Big Time</span><span class="font-display font-bold">GH₵ 45</span></div>
                        <div class="flex justify-between text-[#f0f4f8]"><span>20GB Big Time</span><span class="font-display font-bold">GH₵ 80</span></div>
                        <div class="flex justify-between text-[#f0f4f8]"><span>30GB Big Time</span><span class="font-display font-bold">GH₵ 110</span></div>
                    </div>
                    <a href="#" @click.prevent="localStorage.getItem('token') ? window.location.href='/bundles?network=telecel' : window.location.href='/login'" class="btn-secondary w-full py-3 rounded-xl font-display font-semibold text-center block border border-[#2a3548] hover:border-[#00d4aa]">Buy Telecel</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA (sample style) -->
    <section class="relative py-24 z-10">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="reveal font-display text-4xl sm:text-5xl font-bold mb-6 text-[#f0f4f8]">Ready to Get Started?</h2>
            <p class="reveal reveal-delay-1 text-[#8896ab] text-lg mb-10 max-w-2xl mx-auto">Create an account and buy your first bundle in under a minute.</p>
            <div class="reveal reveal-delay-2 flex flex-wrap justify-center gap-4">
                <a href="/signup" class="btn-primary px-10 py-4 rounded-xl font-display font-semibold text-base">Create Free Account</a>
                <a href="/login" class="btn-secondary px-10 py-4 rounded-xl font-display font-semibold text-base">Sign In</a>
            </div>
        </div>
    </section>

    <!-- Footer (sample structure) -->
    <footer class="border-t border-[#2a3548] py-12 bg-[#111827]/20 z-10">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8 mb-12">
                <div class="col-span-2">
                    <div class="mb-4">
                        <img src="{{ asset('assets/l1.png') }}" alt="{{ \App\Services\SettingsService::get('site_name', 'R2BMarket') }}" class="h-16 w-auto object-contain">
                    </div>
                    <p class="text-[#8896ab] text-sm max-w-xs">Instant data bundles for MTN, AirtelTigo & Telecel in Ghana.</p>
                </div>
                <div>
                    <div class="font-display font-semibold mb-4 text-[#f0f4f8]">Product</div>
                    <ul class="space-y-2 text-[#8896ab] text-sm">
                        <li><a href="/bundles" class="hover:text-[#00d4aa] transition-colors">Bundles</a></li>
                        <li><a href="/support" class="hover:text-[#00d4aa] transition-colors">Support</a></li>
                        <li><a href="/reseller" class="hover:text-[#00d4aa] transition-colors">Reseller</a></li>
                    </ul>
                </div>
                <div>
                    <div class="font-display font-semibold mb-4 text-[#f0f4f8]">Account</div>
                    <ul class="space-y-2 text-[#8896ab] text-sm">
                        <li><a href="/login" class="hover:text-[#00d4aa] transition-colors">Login</a></li>
                        <li><a href="/signup" class="hover:text-[#00d4aa] transition-colors">Sign up</a></li>
                        <li><a href="/dashboard" class="hover:text-[#00d4aa] transition-colors">Dashboard</a></li>
                    </ul>
                </div>
                <div>
                    <div class="font-display font-semibold mb-4 text-[#f0f4f8]">Legal</div>
                    <ul class="space-y-2 text-[#8896ab] text-sm">
                        <li><a href="/privacy" class="hover:text-[#00d4aa] transition-colors">Privacy</a></li>
                        <li><a href="/terms" class="hover:text-[#00d4aa] transition-colors">Terms</a></li>
                    </ul>
                </div>
            </div>
            <div class="pt-8 border-t border-[#2a3548] flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-[#8896ab] text-sm">© {{ date('Y') }} {{ \App\Services\SettingsService::get('site_name', 'R2BMarket') }} Ghana. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var revealElements = document.querySelectorAll('.reveal');
            var revealObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) entry.target.classList.add('visible');
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
            revealElements.forEach(function(el) { revealObserver.observe(el); });
        });
    </script>
</div>
@endsection
