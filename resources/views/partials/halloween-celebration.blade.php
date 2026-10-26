@if(\App\Models\Setting::isHalloweenTheme())
<div x-data="halloweenCelebration()" 
     x-init="initCelebration()"
     x-show="show" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     @keydown.escape.window="dismiss()"
     class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-hidden select-none"
     style="display: none; background: radial-gradient(circle at center, rgba(30, 8, 48, 0.94) 0%, rgba(10, 3, 18, 0.98) 100%); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);">

    <!-- Background Spooky Ambience Orbs & Eerie Fog -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-[32rem] h-[32rem] rounded-full bg-orange-600/25 blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[32rem] h-[32rem] rounded-full bg-purple-700/30 blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
    </div>

    <!-- Swarm of Realistic Bats Flying Across the Entire Screen (Both Directions) -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-20">
        <!-- Bat 1: Massive Foreground Vampire Bat swooping Left to Right -->
        <div class="absolute animate-bat-fly-1 top-[8%] z-30">
            <img src="{{ asset('images/halloween/realistic-bat-1.png') }}" 
                 class="w-40 sm:w-56 h-auto drop-shadow-[0_10px_25px_rgba(0,0,0,0.9)] animate-bat-flap-1" 
                 alt="Realistic Bat">
        </div>

        <!-- Bat 2: Huge Realistic Bat swooping Right to Left (Flipped) -->
        <div class="absolute animate-bat-fly-2 top-[24%] z-20">
            <img src="{{ asset('images/halloween/realistic-bat-2.png') }}" 
                 class="w-32 sm:w-48 h-auto drop-shadow-[0_8px_20px_rgba(0,0,0,0.85)] animate-bat-flap-2" 
                 alt="Realistic Bat">
        </div>

        <!-- Bat 3: Diagonal Swooper Left to Right across mid-viewport -->
        <div class="absolute animate-bat-fly-3 top-[44%] z-20">
            <img src="{{ asset('images/halloween/realistic-bat-1.png') }}" 
                 class="w-28 sm:w-38 h-auto drop-shadow-[0_8px_18px_rgba(0,0,0,0.85)] animate-bat-flap-1" 
                 alt="Realistic Bat">
        </div>

        <!-- Bat 4: Right to Left hunter bat dipping through lower screen -->
        <div class="absolute animate-bat-fly-4 top-[65%] z-30">
            <img src="{{ asset('images/halloween/realistic-bat-2.png') }}" 
                 class="w-36 sm:w-50 h-auto drop-shadow-[0_10px_22px_rgba(0,0,0,0.9)] animate-bat-flap-2" 
                 alt="Realistic Bat">
        </div>

        <!-- Bat 5: High Altitude Fast Chaser Left to Right -->
        <div class="absolute animate-bat-fly-5 top-[16%] z-10">
            <img src="{{ asset('images/halloween/realistic-bat-1.png') }}" 
                 class="w-22 sm:w-30 h-auto drop-shadow-[0_6px_14px_rgba(0,0,0,0.8)] animate-bat-flap-1" 
                 alt="Realistic Bat">
        </div>

        <!-- Bat 6: Foreground Screamer swooping Right to Left across top -->
        <div class="absolute animate-bat-fly-6 top-[78%] z-20">
            <img src="{{ asset('images/halloween/realistic-bat-2.png') }}" 
                 class="w-30 sm:w-42 h-auto drop-shadow-[0_8px_20px_rgba(0,0,0,0.85)] animate-bat-flap-2" 
                 alt="Realistic Bat">
        </div>
    </div>

    <!-- Floating Fiery Sparks & Embers Particles -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-10">
        <span class="absolute animate-ember text-orange-400 text-lg drop-shadow-[0_0_8px_#ff6600]" style="bottom: 30%; left: 45%; animation-delay: 0.1s;">✦</span>
        <span class="absolute animate-ember text-amber-300 text-xl drop-shadow-[0_0_10px_#ff9900]" style="bottom: 32%; left: 52%; animation-delay: 0.6s;">✨</span>
        <span class="absolute animate-ember text-red-500 text-sm drop-shadow-[0_0_6px_#ff2200]" style="bottom: 28%; left: 48%; animation-delay: 1.1s;">•</span>
        <span class="absolute animate-ember text-orange-300 text-base drop-shadow-[0_0_8px_#ff7700]" style="bottom: 35%; left: 42%; animation-delay: 1.6s;">✦</span>
        <span class="absolute animate-treat-float text-3xl sm:text-4xl" style="top: 22%; left: 12%; animation-delay: 0.3s;">🍬</span>
        <span class="absolute animate-treat-float text-3xl sm:text-4xl" style="top: 28%; right: 14%; animation-delay: 0.7s;">🍭</span>
        <span class="absolute animate-treat-float text-3xl sm:text-4xl" style="top: 68%; left: 16%; animation-delay: 0.5s;">🍫</span>
        <span class="absolute animate-treat-float text-3xl sm:text-4xl" style="top: 64%; right: 18%; animation-delay: 0.9s;">🍬</span>
        <span class="absolute animate-treat-float text-3xl sm:text-4xl" style="top: 75%; left: 48%; animation-delay: 1.2s;">👻</span>
    </div>

    <!-- Dismiss Button in Top-Right -->
    <button @click="dismiss()" 
            class="absolute top-6 right-6 z-40 px-4 py-2 bg-white/10 hover:bg-white/20 text-orange-200 hover:text-white rounded-full border border-orange-500/30 text-xs sm:text-sm font-bold flex items-center gap-1.5 transition-all shadow-lg focus:outline-none">
        <i class="fas fa-times"></i>
        <span>Dismiss</span>
    </button>

    <!-- Center Stage: Realistic Evil Laughing Jack-o'-Lantern & Trick or Treat Banner -->
    <div class="relative z-30 max-w-lg w-full flex flex-col items-center text-center p-4 sm:p-6 cursor-pointer" @click="dismiss()">
        
        <!-- Realistic Evil Pumpkin with Evil Laugh Chuckle & Burning Fiery Aura -->
        <div class="relative animate-pumpkin-appear mb-3">
            <!-- Pulsating Fire Aura radiating from inside the Pumpkin -->
            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-orange-600/50 via-amber-500/60 to-red-600/50 blur-3xl animate-flame-aura"></div>
            
            <div class="relative animate-evil-laugh animate-pumpkin-chuckle">
                <img src="{{ asset('images/halloween/realistic-evil-pumpkin.png') }}" 
                     class="w-64 sm:w-80 h-auto drop-shadow-[0_0_40px_rgba(255,100,0,0.95)] filter" 
                     alt="Realistic Evil Laughing Pumpkin">
            </div>
        </div>

        <!-- Trick or Treat Banner with Fiery Neon Text Shadows -->
        <div class="space-y-3 w-full">
            <div class="flex items-center justify-center gap-2 sm:gap-4 select-none">
                <span class="text-3xl sm:text-5xl animate-bounce filter drop-shadow-[0_0_15px_rgba(255,140,0,0.9)]" style="animation-duration: 2s;">🎃</span>
                <h1 class="text-4xl sm:text-6xl font-black font-creepster tracking-widest text-[#ffaa00] animate-bounce-gentle"
                    style="text-shadow: 0 0 14px #ff8800, 0 0 28px #ff4400, 0 0 45px #cc1100, 0 4px 8px #000000;">
                    <span x-text="title">TRICK OR TREAT!</span>
                </h1>
                <span class="text-3xl sm:text-5xl animate-bounce filter drop-shadow-[0_0_15px_rgba(255,140,0,0.9)]" style="animation-duration: 2s; animation-delay: 0.3s;">🎃</span>
            </div>

            <div class="bg-[#150722]/92 border-2 border-orange-500/50 rounded-2xl p-4 sm:p-5 shadow-[0_0_35px_rgba(255,90,0,0.4)] backdrop-blur-md">
                <p class="text-lg sm:text-2xl font-bold text-white flex items-center justify-center gap-2.5 drop-shadow-md">
                    <i class="fas fa-file-invoice text-amber-400"></i>
                    <span x-text="message">Estimate moved to Ready to Invoice!</span>
                </p>
                <p class="text-xs sm:text-sm text-orange-200/90 font-medium mt-1.5" x-text="submessage">
                    No tricks here, only treats for Finance! 🍬👻
                </p>
            </div>
        </div>

        <p class="text-[11px] text-orange-300/60 mt-3.5 uppercase tracking-widest font-semibold">
            Click anywhere or press Esc to continue
        </p>
    </div>
</div>

<style>
    /* Realistic Bat Flapping Wing Cycles (3D Perspective transformation) */
    @keyframes realisticBatFlap1 {
        0%, 100% {
            transform: scaleY(1) rotate(0deg);
        }
        50% {
            transform: scaleY(0.42) scaleX(0.94) rotate(-3deg);
        }
    }
    .animate-bat-flap-1, .animate-bat-wings {
        animation: realisticBatFlap1 0.26s ease-in-out infinite alternate;
    }

    @keyframes realisticBatFlap2 {
        0%, 100% {
            transform: scaleY(1) rotate(0deg);
        }
        50% {
            transform: scaleY(0.46) scaleX(0.96) rotate(3deg);
        }
    }
    .animate-bat-flap-2 {
        animation: realisticBatFlap2 0.22s ease-in-out infinite alternate;
    }

    /* Bat Flight Paths: Swooping across the full screen from one end to another */
    @keyframes realisticBatFly1 {
        0% {
            left: -220px;
            transform: translateY(0) scale(1.1) rotate(6deg);
            opacity: 0;
        }
        4% { opacity: 1; }
        35% { transform: translateY(-55px) scale(1.18) rotate(-4deg); }
        65% { transform: translateY(35px) scale(1.1) rotate(5deg); }
        96% { opacity: 1; }
        100% {
            left: calc(100vw + 220px);
            transform: translateY(-20px) scale(1) rotate(-6deg);
            opacity: 0;
        }
    }
    .animate-bat-fly-1 {
        animation: realisticBatFly1 4.2s cubic-bezier(0.35, 0.05, 0.25, 1) infinite;
        animation-delay: 0.1s;
    }

    @keyframes realisticBatFly2 {
        0% {
            left: calc(100vw + 200px);
            transform: scaleX(-1) translateY(0) scale(0.95) rotate(5deg);
            opacity: 0;
        }
        4% { opacity: 0.95; }
        40% { transform: scaleX(-1) translateY(-60px) scale(1.05) rotate(-6deg); }
        75% { transform: scaleX(-1) translateY(25px) scale(0.95) rotate(4deg); }
        96% { opacity: 0.95; }
        100% {
            left: -200px;
            transform: scaleX(-1) translateY(-10px) scale(0.9) rotate(-3deg);
            opacity: 0;
        }
    }
    .animate-bat-fly-2 {
        animation: realisticBatFly2 3.8s cubic-bezier(0.35, 0.05, 0.25, 1) infinite;
        animation-delay: 0.6s;
    }

    @keyframes realisticBatFly3 {
        0% {
            left: -180px;
            transform: translateY(0) scale(0.85) rotate(8deg);
            opacity: 0;
        }
        4% { opacity: 0.95; }
        50% { transform: translateY(-40px) scale(0.95) rotate(-5deg); }
        96% { opacity: 0.95; }
        100% {
            left: calc(100vw + 180px);
            transform: translateY(30px) scale(0.8) rotate(4deg);
            opacity: 0;
        }
    }
    .animate-bat-fly-3 {
        animation: realisticBatFly3 4.6s cubic-bezier(0.35, 0.05, 0.25, 1) infinite;
        animation-delay: 1.3s;
    }

    @keyframes realisticBatFly4 {
        0% {
            left: calc(100vw + 180px);
            transform: scaleX(-1) translateY(-20px) scale(0.8) rotate(6deg);
            opacity: 0;
        }
        4% { opacity: 0.9; }
        45% { transform: scaleX(-1) translateY(40px) scale(0.9) rotate(-4deg); }
        96% { opacity: 0.9; }
        100% {
            left: -180px;
            transform: scaleX(-1) translateY(-30px) scale(0.75) rotate(2deg);
            opacity: 0;
        }
    }
    .animate-bat-fly-4 {
        animation: realisticBatFly4 3.5s cubic-bezier(0.35, 0.05, 0.25, 1) infinite;
        animation-delay: 1.8s;
    }

    @keyframes realisticBatFly5 {
        0% {
            left: -160px;
            transform: translateY(0) scale(0.7) rotate(-4deg);
            opacity: 0;
        }
        4% { opacity: 0.85; }
        50% { transform: translateY(-30px) scale(0.78) rotate(6deg); }
        96% { opacity: 0.85; }
        100% {
            left: calc(100vw + 160px);
            transform: translateY(15px) scale(0.65) rotate(-3deg);
            opacity: 0;
        }
    }
    .animate-bat-fly-5 {
        animation: realisticBatFly5 5.1s cubic-bezier(0.35, 0.05, 0.25, 1) infinite;
        animation-delay: 0.3s;
    }

    @keyframes realisticBatFly6 {
        0% {
            left: calc(100vw + 220px);
            transform: scaleX(-1) translateY(10px) scale(1.15) rotate(4deg);
            opacity: 0;
        }
        4% { opacity: 1; }
        50% { transform: scaleX(-1) translateY(-50px) scale(1.25) rotate(-6deg); }
        96% { opacity: 1; }
        100% {
            left: -220px;
            transform: scaleX(-1) translateY(20px) scale(1.1) rotate(5deg);
            opacity: 0;
        }
    }
    .animate-bat-fly-6 {
        animation: realisticBatFly6 4.0s cubic-bezier(0.35, 0.05, 0.25, 1) infinite;
        animation-delay: 2.1s;
    }

    /* Realistic Pumpkin Pop-In */
    @keyframes pumpkinAppear {
        0% {
            transform: scale(0.3) rotate(-15deg);
            opacity: 0;
        }
        60% {
            transform: scale(1.12) rotate(3deg);
            opacity: 1;
        }
        80% {
            transform: scale(0.96) rotate(-1deg);
        }
        100% {
            transform: scale(1) rotate(0deg);
            opacity: 1;
        }
    }
    .animate-pumpkin-appear {
        animation: pumpkinAppear 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    /* Realistic Pumpkin Evil Laugh Animation */
    @keyframes evilLaugh {
        0% {
            transform: scale(1) rotate(0deg);
            filter: drop-shadow(0 0 30px rgba(255, 95, 0, 0.75));
        }
        8% {
            transform: scale(1.02) rotate(-1.5deg);
        }
        14% {
            /* Sinister chuckle shake 1 */
            transform: scale(1.07) rotate(2deg) translateY(-5px);
            filter: drop-shadow(0 0 42px rgba(255, 80, 0, 0.95)) brightness(1.12);
        }
        20% {
            transform: scale(1.03) rotate(-2deg) translateY(2px);
        }
        26% {
            /* Sinister chuckle shake 2 */
            transform: scale(1.09) rotate(2.5deg) translateY(-6px);
            filter: drop-shadow(0 0 50px rgba(255, 110, 0, 1)) brightness(1.2);
        }
        32% {
            transform: scale(1.04) rotate(-1.5deg) translateY(1px);
        }
        38% {
            /* Full-blown cackle shudder */
            transform: scale(1.12) rotate(3deg) translateY(-8px);
            filter: drop-shadow(0 0 60px rgba(255, 60, 0, 1)) brightness(1.28);
        }
        45% {
            transform: scale(1.07) rotate(-2deg) translateY(3px);
        }
        52% {
            transform: scale(1.11) rotate(2deg) translateY(-6px);
            filter: drop-shadow(0 0 55px rgba(255, 90, 0, 1)) brightness(1.22);
        }
        60% {
            transform: scale(1.05) rotate(-1deg) translateY(2px);
        }
        70% {
            transform: scale(1.08) rotate(1.5deg) translateY(-4px);
        }
        82% {
            transform: scale(1.02) rotate(-0.5deg) translateY(0);
        }
        100% {
            transform: scale(1) rotate(0deg);
            filter: drop-shadow(0 0 30px rgba(255, 95, 0, 0.75));
        }
    }
    .animate-evil-laugh {
        animation: evilLaugh 2.6s ease-in-out infinite;
    }

    /* Pulsating Fire Aura */
    @keyframes flameAuraPulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.65;
        }
        35% {
            transform: scale(1.2);
            opacity: 0.95;
        }
        70% {
            transform: scale(1.08);
            opacity: 0.8;
        }
    }
    .animate-flame-aura {
        animation: flameAuraPulse 1.8s ease-in-out infinite;
    }

    /* Floating Fire Embers */
    @keyframes emberRise {
        0% {
            transform: translateY(0) scale(0.6) rotate(0deg);
            opacity: 0;
        }
        20% { opacity: 1; }
        80% { opacity: 0.8; }
        100% {
            transform: translateY(-90px) scale(1.2) rotate(45deg);
            opacity: 0;
        }
    }
    .animate-ember {
        animation: emberRise 2.4s ease-out infinite;
    }

    /* Treat Floaters */
    @keyframes treatFloat {
        0% {
            transform: translateY(0) scale(0.8) rotate(0deg);
            opacity: 0;
        }
        20% { opacity: 1; }
        50% {
            transform: translateY(-25px) scale(1.1) rotate(15deg);
        }
        80% { opacity: 1; }
        100% {
            transform: translateY(-50px) scale(0.9) rotate(-15deg);
            opacity: 0;
        }
    }
    .animate-treat-float {
        animation: treatFloat 3s ease-in-out infinite;
    }

    @keyframes bounceGentle {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }
    .animate-bounce-gentle {
        animation: bounceGentle 2s ease-in-out infinite;
    }

    @media (prefers-reduced-motion: reduce) {
        .animate-bat-fly-1, .animate-bat-fly-2, .animate-bat-fly-3, .animate-bat-fly-4, .animate-bat-fly-5, .animate-bat-fly-6,
        .animate-bat-flap-1, .animate-bat-flap-2, .animate-pumpkin-appear, .animate-evil-laugh, .animate-flame-aura,
        .animate-ember, .animate-treat-float, .animate-bounce-gentle {
            animation: none !important;
            transform: none !important;
        }
    }
</style>

<script>
    function halloweenCelebration() {
        return {
            show: false,
            title: 'TRICK OR TREAT!',
            message: 'Estimate moved to Ready to Invoice!',
            submessage: 'No tricks here, only treats for Finance! 🍬👻',
            timer: null,

            initCelebration() {
                // Register global window trigger for manual invocation
                window.triggerHalloweenCelebration = (customTitle, customMessage, customSub) => {
                    this.trigger(customTitle, customMessage, customSub);
                };

                // Trigger automatically from session flash with anti-double-firing protection
                @if(session('halloween_ready_to_invoice') || session('halloween_celebrate'))
                    const celebrationKey = 'halloween_celebration_ack';
                    const lastShown = sessionStorage.getItem(celebrationKey);
                    const now = Date.now();
                    // Guarantee it can only fire once per transition window (4 seconds debounce)
                    if (!lastShown || (now - parseInt(lastShown, 10)) > 4000) {
                        sessionStorage.setItem(celebrationKey, now.toString());
                        this.$nextTick(() => {
                            this.trigger(
                                'TRICK OR TREAT!',
                                'Estimate moved to Ready to Invoice!',
                                'No tricks here, only treats for Finance! 🍬👻'
                            );
                        });
                    }
                @endif
            },

            trigger(customTitle, customMessage, customSub) {
                if (customTitle) this.title = customTitle;
                if (customMessage) this.message = customMessage;
                if (customSub) this.submessage = customSub;

                this.show = true;
                this.playEvilLaughSound();

                if (this.timer) clearTimeout(this.timer);
                // Auto-dismiss after 4.8 seconds
                this.timer = setTimeout(() => {
                    this.dismiss();
                }, 4800);
            },

            dismiss() {
                this.show = false;
                if (this.timer) {
                    clearTimeout(this.timer);
                    this.timer = null;
                }
            },

            playEvilLaughSound() {
                try {
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContext) return;
                    const ctx = new AudioContext();
                    if (ctx.state === 'suspended') {
                        ctx.resume().catch(() => {});
                    }

                    const now = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    const filter = ctx.createBiquadFilter();

                    filter.type = 'lowpass';
                    filter.frequency.setValueAtTime(340, now);

                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(95, now);

                    // Eerie rhythmic evil laugh frequency modulation (Heh-Heh-Mwa-ha-ha)
                    const laughNotes = [90, 80, 105, 75, 115, 70, 125, 68, 58];
                    laughNotes.forEach((freq, idx) => {
                        const t = now + (idx * 0.20);
                        osc.frequency.exponentialRampToValueAtTime(freq, t + 0.04);
                        gain.gain.setValueAtTime(0.01, t);
                        gain.gain.linearRampToValueAtTime(0.22, t + 0.05);
                        gain.gain.exponentialRampToValueAtTime(0.01, t + 0.17);
                    });

                    osc.connect(filter);
                    filter.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start(now);
                    osc.stop(now + 2.2);
                } catch (e) {
                    // Audio errors silently caught
                }
            }
        };
    }
</script>
@endif
