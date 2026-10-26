<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Creative Kiwi</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    
    <!-- PWA Web App Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="{{ \App\Models\Setting::isHalloweenTheme() ? '#1a0b2e' : '#8035ca' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Creative Kiwi">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa-icon-192.png') }}">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Creepster&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @php
        $isHalloween = \App\Models\Setting::isHalloweenTheme();
    @endphp

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-creepster { font-family: 'Creepster', cursive; }
        @keyframes floatBat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(4deg); }
        }
        .animate-float-bat { animation: floatBat 4s ease-in-out infinite; }
        @keyframes spiderSwing {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(8deg); }
            75% { transform: rotate(-8deg); }
        }
        .animate-spider-swing { transform-origin: top center; animation: spiderSwing 3.5s ease-in-out infinite; }

        @if($isHalloween)
        /* Prevent browser autofill from turning inputs light blue or white */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #190c24 inset !important;
            -webkit-text-fill-color: #f3f4f6 !important;
            caret-color: #f3f4f6 !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        @endif
    </style>
</head>

<body class="min-h-screen min-h-[100dvh] flex items-center justify-center p-4 sm:p-6 bg-fixed relative overflow-x-hidden"
    @if($isHalloween)
        style="background: url('{{ asset('images/halloween/login-bg.jpg') }}') center center / cover no-repeat fixed, linear-gradient(135deg, #12061f 0%, #1a0828 50%, #0d0416 100%);"
    @else
        style="background: linear-gradient(135deg, #ff0878 0%, #8035ca 35%, #0057be 70%, #2fc9c3 100%);"
    @endif>

    <!-- Animated background shapes for depth -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        @if($isHalloween)
            <div class="absolute -top-[10%] -left-[10%] w-[45%] h-[45%] rounded-full bg-orange-600/10 blur-3xl animate-pulse"></div>
            <div class="absolute top-[60%] -right-[5%] w-[35%] h-[35%] rounded-full bg-purple-600/15 blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
            <!-- Atmospheric Bats floating in background -->
            <div class="absolute top-[12%] left-[18%] opacity-35 animate-float-bat hidden md:block">
                <img src="{{ asset('images/halloween/bats.svg') }}" class="w-16 h-auto" alt="bat">
            </div>
            <div class="absolute top-[28%] right-[16%] opacity-30 animate-float-bat hidden md:block" style="animation-delay: 1.5s;">
                <img src="{{ asset('images/halloween/bats.svg') }}" class="w-20 h-auto transform -scale-x-100" alt="bat">
            </div>
        @else
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-white/10 blur-3xl animate-pulse"></div>
            <div class="absolute top-[60%] -right-[5%] w-[30%] h-[30%] rounded-full bg-white/10 blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        @endif
    </div>

    <!-- Login Card -->
    <div class="relative z-10 w-full max-w-[395px] my-auto p-6 sm:p-8 rounded-[28px] shadow-2xl border transform transition-all duration-300 {{ $isHalloween ? 'bg-[#160a22]/85 backdrop-blur-xl border-[#8a385f]/40 hover:border-orange-500/40 shadow-[0_0_30px_rgba(255,90,0,0.12),0_20px_50px_rgba(0,0,0,0.8)]' : 'bg-white/95 backdrop-blur-md border-white/20 hover:shadow-white/10' }}">
        
        @if($isHalloween)
            <!-- Spiderweb decorations in top corners -->
            <img src="{{ asset('images/halloween/spiderweb.svg') }}" class="absolute top-0 left-0 w-24 h-24 pointer-events-none opacity-40 select-none z-10" alt="spiderweb">
            <img src="{{ asset('images/halloween/spiderweb.svg') }}" class="absolute top-0 right-0 w-24 h-24 pointer-events-none opacity-40 select-none transform -scale-x-100 z-10" alt="spiderweb">
            
            <!-- Hanging spider on thread on left of logo -->
            <div class="absolute top-0 left-6 sm:left-7 pointer-events-none select-none animate-spider-swing z-10">
                <img src="{{ asset('images/halloween/hanging-spider.svg') }}" class="w-8 h-14" alt="hanging spider">
            </div>
        @endif

        <div class="flex flex-col items-center mb-5 relative">
            <div class="relative bg-white px-6 py-2.5 sm:px-7 sm:py-3 rounded-2xl shadow-sm mb-3 inline-flex items-center justify-center">
                @if($isHalloween)
                    <!-- Witch hat tilted on top right corner of logo container with carved pumpkin badge -->
                    <img src="{{ asset('images/halloween/witch-hat.svg') }}" 
                         class="absolute -top-7 -right-7 sm:-top-8 sm:-right-8 w-16 sm:w-20 h-auto pointer-events-none transform rotate-12 drop-shadow-[0_4px_8px_rgba(0,0,0,0.6)] z-20" 
                         alt="witch hat">
                @endif
                <img src="{{ asset('images/logo_loops.png') }}" alt="Creative Kiwi" class="h-10 sm:h-12 w-auto relative z-10">
            </div>
            
            @if($isHalloween)
                <!-- Spooky Welcome Back Title with Flanking Orange Bats -->
                <div class="flex items-center justify-center gap-2.5 mt-1 mb-1">
                    <img src="{{ asset('images/halloween/orange-bat.svg') }}" class="w-6 sm:w-7 h-auto animate-float-bat opacity-95" alt="bat">
                    <h2 class="text-3xl sm:text-[34px] leading-tight font-normal text-transparent bg-clip-text bg-gradient-to-b from-[#ffb347] via-[#ff7a00] to-[#e65100] tracking-wider font-creepster" style="filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.95)) drop-shadow(0 0 10px rgba(255, 115, 0, 0.4));">
                        Welcome Back
                    </h2>
                    <img src="{{ asset('images/halloween/orange-bat.svg') }}" class="w-6 sm:w-7 h-auto animate-float-bat opacity-95 transform -scale-x-100" style="animation-delay: 1.8s;" alt="bat">
                </div>
                <p class="text-gray-400 text-xs sm:text-sm font-normal text-center">Please enter your credentials</p>
            @else
                <h2 class="text-2xl sm:text-3xl font-black text-gray-800 tracking-tight text-center">Welcome Back</h2>
                <p class="text-gray-500 mt-1 sm:mt-2 text-xs sm:text-sm font-medium">Please enter your credentials</p>
            @endif
        </div>

        @if (session('success'))
            <div class="{{ $isHalloween ? 'bg-orange-950/60 border-orange-500 text-orange-200' : 'bg-emerald-50 border-emerald-500 text-emerald-700' }} border-l-4 p-4 mb-5 rounded-r-xl shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-lg {{ $isHalloween ? 'text-orange-400' : 'text-emerald-500' }}"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-bold">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="{{ $isHalloween ? 'bg-red-950/60 border-red-500 text-red-200' : 'bg-red-50 border-red-500 text-red-700' }} border-l-4 p-4 mb-5 rounded-r-xl shadow-sm animate-shake">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-bold">
                            {{ $errors->first() }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="space-y-4" x-data="{ showPassword: false }">
            @csrf
            <!-- Email Address Field -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs sm:text-sm font-semibold {{ $isHalloween ? 'text-gray-200' : 'text-gray-700' }} ml-0.5">Email Address</label>
                <div class="relative group">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center {{ $isHalloween ? 'text-[#ff7700]' : 'text-gray-400 group-focus-within:text-blue-600' }} transition-colors pointer-events-none">
                        <i class="fas fa-envelope text-sm"></i>
                    </span>
                    <input type="email" name="email" id="email" required
                        value="{{ old('email') }}"
                        placeholder="name@company.com"
                        class="block w-full pl-10 pr-4 py-3 {{ $isHalloween ? 'bg-[#190c24]/90 border border-orange-500/40 text-gray-100 placeholder-gray-500/80 focus:bg-[#201030] focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30' : 'bg-gray-50 border-2 border-transparent text-gray-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }} rounded-xl outline-none transition-all duration-300 text-sm font-medium">
                </div>
            </div>

            <!-- Password Field -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-xs sm:text-sm font-semibold {{ $isHalloween ? 'text-gray-200' : 'text-gray-700' }} ml-0.5">Password</label>
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold {{ $isHalloween ? 'text-[#ff8f1f] hover:text-[#ffa94d]' : 'text-blue-600 hover:text-blue-800' }} hover:underline transition-colors">
                        Forgot Password?
                    </a>
                </div>
                <div class="relative group" x-data="{ focused: false }">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center {{ $isHalloween ? 'text-[#ff7700]' : 'text-gray-400' }} pointer-events-none">
                        <i class="fas fa-lock text-sm"></i>
                    </span>
                    <input :type="showPassword ? 'text' : 'password'" name="password" id="password" required
                        @focus="focused = true" @blur="focused = false"
                        placeholder="••••••••"
                        class="block w-full pl-10 pr-11 py-3 {{ $isHalloween ? 'bg-[#190c24]/90 border border-orange-500/40 text-gray-100 placeholder-gray-500/80 focus:bg-[#201030] focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30' : 'bg-gray-50 border-2 border-transparent text-gray-700 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-100' }} rounded-xl outline-none transition-all duration-300 text-sm font-medium">
                    
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center {{ $isHalloween ? 'text-[#8b799e] hover:text-gray-200' : 'text-gray-400 hover:text-blue-600' }} transition-colors focus:outline-none">
                        <i class="fas text-sm" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <!-- Keep Me Signed In -->
            <div class="flex items-center justify-between pt-0.5">
                <label class="flex items-center text-xs sm:text-sm {{ $isHalloween ? 'text-gray-300' : 'text-gray-600' }} font-normal cursor-pointer select-none">
                    <input type="checkbox" name="remember" value="1" checked class="rounded border-orange-500/50 bg-[#190c24] {{ $isHalloween ? 'text-orange-500 focus:ring-orange-500 accent-orange-500' : 'text-blue-600 focus:ring-blue-500' }} h-4 w-4 mr-2.5 cursor-pointer">
                    <span>Keep me signed in</span>
                </label>
            </div>

            <!-- Sign In Button -->
            <div class="pt-1">
                @if($isHalloween)
                    <button type="submit"
                        class="relative w-full block focus:outline-none group transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                        <span class="sr-only">Sign In</span>
                        <img src="{{ asset('images/halloween/sign-in-btn.png') }}"
                            alt="Sign In"
                            class="w-full h-auto block select-none pointer-events-none drop-shadow-[0_0_18px_rgba(255,107,0,0.55)] group-hover:drop-shadow-[0_0_28px_rgba(255,115,0,0.9)] transition-all">
                    </button>
                @else
                    <button type="submit"
                        class="w-full flex justify-center items-center py-3.5 sm:py-4 px-6 border border-transparent rounded-xl shadow-xl text-base sm:text-lg font-black text-white bg-blue-600 hover:bg-blue-700 hover:shadow-blue-500/25 focus:outline-none focus:ring-4 focus:ring-blue-200 transition-all duration-300 transform active:scale-95">
                        Sign In <i class="fas fa-arrow-right ml-2 text-sm"></i>
                    </button>
                @endif
            </div>

            <!-- PWA Install Button on Login Screen -->
            <div class="mt-3.5 pwa-install-btn">
                @if($isHalloween)
                    <button type="button" class="pwa-install-trigger w-full flex items-center justify-center gap-2 py-3 px-4 bg-[#150a22]/90 hover:bg-[#200e33] text-[#ff8f1f] hover:text-orange-300 text-xs sm:text-sm font-bold rounded-xl border border-orange-500/40 hover:border-orange-500/70 shadow-sm transition-all active:scale-98">
                        <i class="fas fa-download text-[#ff7700]"></i>
                        <span>Install Creative Kiwi App</span>
                    </button>
                @else
                    <button type="button" class="pwa-install-trigger w-full flex items-center justify-center gap-2 py-3 px-4 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs sm:text-sm font-bold rounded-xl border border-gray-200 shadow-2xs hover:border-gray-300 transition-all active:scale-98">
                        <i class="fas fa-download text-brand-purple"></i>
                        <span>Install Creative Kiwi App</span>
                    </button>
                @endif
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-[11px] {{ $isHalloween ? 'text-[#78668c]' : 'text-gray-400' }} font-medium uppercase tracking-widest">&copy; 2026 Creative Kiwi. All rights reserved.</p>
        </div>
    </div>

    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .animate-shake { animation: shake 0.4s ease-in-out; }
    </style>
    <!-- PWA Install Prompts & Controller -->
    @include('partials.pwa-install')
</body>

</html>