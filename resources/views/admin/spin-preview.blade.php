<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GHS Spin Master – Admin Preview</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/spin.js'])
    @endif
    <style>
        :root {
            --gh-gold: #F0C232;
            --gh-red: #DB4650;
            --gh-green: #D4A81F;
            --dark-surface: #0A2038;
        }

        body {
            background: var(--color-brand-navy);
            font-family: inherit;
            perspective: 1500px;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .wheel-container {
            transform: rotateX(10deg);
            transform-style: preserve-3d;
        }

        #wheel {
            width: 450px;
            height: 450px;
            border-radius: 50%;
            position: relative;
            border: 15px solid #334155;
            outline: 6px solid var(--gh-gold);
            /* 7 segments: thin light divider then color; last segment = red gradient */
            background:
                conic-gradient(
                    rgba(255,255,255,0.5) 0deg 0.5deg,
                    #F0C232 0.5deg 51.43deg,
                    rgba(255,255,255,0.5) 51.43deg 51.93deg,
                    #DB4650 51.93deg 102.86deg,
                    rgba(255,255,255,0.5) 102.86deg 103.36deg,
                    #143A5A 103.36deg 154.29deg,
                    rgba(255,255,255,0.5) 154.29deg 154.79deg,
                    #F0C232 154.79deg 205.72deg,
                    rgba(255,255,255,0.5) 205.72deg 206.22deg,
                    #DB4650 206.22deg 257.14deg,
                    rgba(255,255,255,0.5) 257.14deg 257.64deg,
                    #143A5A 257.64deg 308.57deg,
                    rgba(255,255,255,0.5) 308.57deg 308.97deg,
                    #F0C232 308.97deg 360deg
                );
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
            transition: transform 6s cubic-bezier(0.2, 0, 0.1, 1);
            transform-style: preserve-3d;
        }

        .prize-segment {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 40px;
            font-size: 2.4rem;
            font-weight: 900;
            color: white;
            text-shadow: 0 2px 8px rgba(0,0,0,0.6), 0 0 20px rgba(0,0,0,0.3);
        }

        .prize-segment span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .prize-segment.prize-luck span {
            flex-direction: column;
            gap: 2px;
        }
        .prize-segment.prize-luck .luck-title { font-size: 1.7rem; font-weight: 900; line-height: 1.1; }
        .prize-segment.prize-luck .luck-sub { font-size: 1rem; font-weight: 700; opacity: 0.95; line-height: 1.1; }

        .prize-segment.prize-off span {
            flex-direction: column;
            gap: 0;
            font-size: 2.2rem;
        }
        .prize-segment.prize-off .off-pct { font-size: 2.3rem; line-height: 1; }
        .prize-segment.prize-off .off-word { font-size: 1.4rem; font-weight: 900; line-height: 1.1; }

        .ticker {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 70px;
            background: var(--gh-red);
            clip-path: polygon(0% 0%, 100% 0%, 50% 100%);
            z-index: 100;
            border-bottom: 5px solid #991b1b;
        }

        .wheel-shine {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: transparent;
            pointer-events: none;
        }

        .win-glow {
            animation: glow-pulse 1s infinite alternate;
        }

        @keyframes glow-pulse {
            from {
                box-shadow: inset 0 4px 24px rgba(255,255,255,0.15), inset 0 -8px 32px rgba(0,0,0,0.35), inset 0 0 0 1px rgba(255,255,255,0.08), 0 0 20px var(--gh-gold);
            }
            to {
                box-shadow: inset 0 4px 24px rgba(255,255,255,0.15), inset 0 -8px 32px rgba(0,0,0,0.35), inset 0 0 0 1px rgba(255,255,255,0.08), 0 0 60px var(--gh-gold);
            }
        }

        @media (max-width: 520px) {
            #wheel { width: 320px; height: 320px; }
            .prize-segment { padding-top: 28px; font-size: 1.5rem; }
            .prize-segment.prize-luck .luck-title { font-size: 1.2rem; }
            .prize-segment.prize-luck .luck-sub { font-size: 0.75rem; }
            .prize-segment.prize-off .off-pct { font-size: 1.6rem; }
            .prize-segment.prize-off .off-word { font-size: 1rem; }
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen pt-20 pb-12">

    <!-- Admin bar: back + preview badge -->
    <div class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-4 py-3 bg-slate-900/95 border-b border-slate-700 backdrop-blur">
        <a href="/admin/dashboard" class="inline-flex items-center gap-2 text-slate-300 hover:text-white font-bold text-sm transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Admin
        </a>
        <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-400 text-xs font-black uppercase tracking-wider">Preview – Coming Soon</span>
    </div>

    <div class="text-center mb-8 sm:mb-10">
        <h2 class="text-white opacity-60 tracking-[0.3em] uppercase text-sm mb-2">Ghana Lucky Spin</h2>
        <h1 class="text-4xl sm:text-6xl font-black text-white italic">WIN <span class="text-yellow-400 underline">GHS</span> DAILY</h1>
    </div>

    <div class="relative wheel-container">
        <div class="ticker" id="ticker"></div>

        <div id="wheel">
            <div class="wheel-shine"></div>

            <!-- Each label rotated to center of its ~51.43° slice (7 segments) -->
            <div class="prize-segment" style="transform: rotate(26deg)"><span>GHS 1</span></div>
            <div class="prize-segment" style="transform: rotate(77deg)"><span>GHS 5</span></div>
            <div class="prize-segment" style="transform: rotate(129deg)"><span>GHS 10</span></div>
            <div class="prize-segment" style="transform: rotate(180deg)"><span>1GB</span></div>
            <div class="prize-segment" style="transform: rotate(231deg)"><span>5GB</span></div>
            <div class="prize-segment prize-off" style="transform: rotate(283deg)"><span><span class="off-pct block">20%</span><span class="off-word block">off</span></span></div>
            <div class="prize-segment prize-luck" style="transform: rotate(334deg)"><span><span class="luck-title block">No Win!</span><span class="luck-sub block"></span></span></div>
        </div>

        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50">
            <button onclick="spin()" id="spin-btn" class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-yellow-500 border-4 border-white shadow-2xl flex items-center justify-center group active:scale-90 transition-transform">
                <span class="text-slate-900 font-black text-lg sm:text-xl group-hover:scale-110 transition-transform">SPIN</span>
            </button>
        </div>
    </div>

    <div id="display-result" class="mt-8 sm:mt-12 text-2xl sm:text-4xl font-black text-yellow-400 transition-all duration-500 scale-0">
        <span id="result-prefix">CONGRATS!</span> <span id="amount" class="text-white"></span>
    </div>

    <script>
        let rotation = 0;
        let spinning = false;

        const prizes = [
            "GHS 1",
            "GHS 5",
            "GHS 10",
            "1GB",
            "5GB",
            "20% off",
            "Better luck next time"
        ];
        const segmentDeg = 360 / 7;

        function spin() {
            if (spinning) return;

            spinning = true;
            const btn = document.getElementById('spin-btn');
            const wheel = document.getElementById('wheel');
            const resultDisplay = document.getElementById('display-result');

            resultDisplay.classList.add('scale-0');
            wheel.classList.remove('win-glow');

            // Pick a random prize first, then rotate so the *center* of that segment stops under the pointer
            const prizeIndex = Math.floor(Math.random() * 7);
            const centerAngle = (prizeIndex + 0.5) * segmentDeg;
            const finalAngle = 360 - centerAngle;
            const totalRotation = (360 * 10) + finalAngle + (Math.random() * 0.5);
            rotation += totalRotation;

            wheel.style.transform = 'rotate(' + rotation + 'deg)';

            setTimeout(function() {
                spinning = false;
                const winner = prizes[prizeIndex];
                var prefixEl = document.getElementById('result-prefix');
                var amountEl = document.getElementById('amount');

                if (winner === "Better luck next time") {
                    prefixEl.textContent = "No Win!";
                    amountEl.textContent = " Try again tomorrow.";
                    amountEl.className = "text-slate-300 text-xl sm:text-2xl";
                    prefixEl.className = "text-slate-300";
                } else {
                    prefixEl.textContent = "CONGRATS!";
                    prefixEl.className = "";
                    amountEl.textContent = " " + winner;
                    amountEl.className = "text-white";
                    wheel.classList.add('win-glow');
                    fireConfetti();
                }
                resultDisplay.classList.remove('scale-0');
                resultDisplay.classList.add('scale-100');
            }, 6000);
        }

        function fireConfetti() {
            if (typeof confetti !== 'function') return;
            var count = 200;
            var defaults = { origin: { y: 0.6 }, zIndex: 9999 };
            function burst() {
                confetti({ ...defaults, particleCount: count, spread: 100, colors: ['#F0C232', '#fef08a', '#22c55e', '#ef4444', '#ffffff'] });
                confetti({ ...defaults, particleCount: count * 0.4, spread: 60, scalar: 1.2 });
                confetti({ ...defaults, particleCount: count * 0.25, spread: 140, scalar: 0.8, decay: 0.9 });
                setTimeout(function() {
                    confetti({ ...defaults, particleCount: 50, angle: 60, spread: 55, origin: { x: 0 } });
                    confetti({ ...defaults, particleCount: 50, angle: 120, spread: 55, origin: { x: 1 } });
                }, 200);
            }
            burst();
            setTimeout(function() { burst(); }, 800);
            setTimeout(function() { burst(); }, 1600);
        }
    </script>
</body>
</html>
