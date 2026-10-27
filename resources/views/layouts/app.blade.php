<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $isHalloween = \App\Models\Setting::isHalloweenTheme();
        $isStaffUser = auth()->check() && in_array(auth()->user()->role, ['Staff', 'HR Admin']);
        $desktopActiveLink = $isHalloween 
            ? 'bg-gradient-to-r from-orange-500/25 to-orange-500/10 text-orange-400 font-bold border border-orange-500/50 shadow-[0_0_15px_rgba(255,120,0,0.25)]' 
            : 'bg-gray-700 text-brand-pink';
        $desktopInactiveLink = $isHalloween 
            ? 'text-gray-300 hover:bg-[#201235] hover:text-orange-300' 
            : 'text-gray-300 hover:bg-gray-700 hover:text-white';
        $mobileActiveLink = $isHalloween 
            ? 'bg-gradient-to-r from-orange-500/25 to-orange-500/10 text-orange-400 font-bold border border-orange-500/50 shadow-[0_0_15px_rgba(255,120,0,0.25)]' 
            : 'bg-gray-700 text-brand-pink font-semibold';
        $mobileInactiveLink = $isHalloween 
            ? 'text-gray-300 hover:bg-[#201235] hover:text-orange-300' 
            : 'text-gray-300 hover:bg-gray-700 hover:text-white';
    @endphp

    <title>{{ $isHalloween ? 'Creative Kiwi 🎃 Spooky Season' : 'Creative Kiwi' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- PWA Web App Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="{{ $isHalloween ? '#1a0b2e' : '#8035ca' }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Creative Kiwi">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa-icon-192.png') }}">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            pink: '{{ \App\Models\Setting::get("brand_pink", "#ff0878") }}',
                            purple: '{{ \App\Models\Setting::get("brand_purple", "#8035ca") }}',
                            blue: '{{ \App\Models\Setting::get("brand_blue", "#0057be") }}',
                            teal: '{{ \App\Models\Setting::get("brand_teal", "#2fc9c3") }}',
                        },
                        primary: '{{ \App\Models\Setting::get("brand_pink", "#ff0878") }}',
                        secondary: '#0057be', // Using blue as secondary
                        dark: '#1f2937',
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tom Select -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Alpine.js Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Quill Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Creepster&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        @if($isHalloween)
        .font-creepster { font-family: 'Creepster', cursive; }
        @keyframes spiderSwing {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(7deg); }
            75% { transform: rotate(-7deg); }
        }
        .animate-spider-swing { transform-origin: top center; animation: spiderSwing 4s ease-in-out infinite; }

        @keyframes ghostPeek {
            0%, 15% {
                opacity: 0;
                transform: translate(-55px, -65px) rotate(-30deg) scale(0.6);
                filter: drop-shadow(0 0 0 rgba(100, 200, 255, 0));
            }
            24% {
                opacity: 0.92;
                transform: translate(6px, 6px) rotate(-6deg) scale(0.95);
                filter: drop-shadow(0 0 10px rgba(0, 210, 255, 0.75));
            }
            32% {
                opacity: 1;
                transform: translate(12px, 10px) rotate(4deg) scale(1.02);
                filter: drop-shadow(0 0 14px rgba(100, 220, 255, 0.9)) drop-shadow(0 4px 12px rgba(168, 85, 247, 0.45));
            }
            40% {
                opacity: 0.95;
                transform: translate(8px, 12px) rotate(-2deg) scale(0.98);
                filter: drop-shadow(0 0 10px rgba(0, 210, 255, 0.8));
            }
            48% {
                opacity: 1;
                transform: translate(14px, 8px) rotate(3deg) scale(1.01);
                filter: drop-shadow(0 0 15px rgba(100, 220, 255, 0.9));
            }
            55% {
                opacity: 0.88;
                transform: translate(4px, 2px) rotate(-10deg) scale(0.9);
                filter: drop-shadow(0 0 8px rgba(0, 210, 255, 0.6));
            }
            62%, 100% {
                opacity: 0;
                transform: translate(-55px, -65px) rotate(-30deg) scale(0.6);
                filter: drop-shadow(0 0 0 rgba(100, 200, 255, 0));
            }
        }
        .animate-ghost-peek {
            transform-origin: top left;
            animation: ghostPeek 9s ease-in-out infinite;
            will-change: transform, opacity;
        }
        @media (prefers-reduced-motion: reduce) {
            .animate-ghost-peek {
                animation: none !important;
                opacity: 0 !important;
            }
        }

        /* Halloween Header Title & Navigation Text Contrast */
        header h2,
        header h2 > *,
        header h2 span:not([class*="bg-"]):not([class*="badge"]),
        header h2 p,
        header h2 strong,
        header h2 b {
            color: #ffffff !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.95), 0 0 12px rgba(0, 0, 0, 0.75) !important;
        }
        header h2 i {
            color: #ff9e2c !important;
            text-shadow: 0 0 8px rgba(255, 158, 44, 0.7) !important;
        }
        header h2 div {
            background-color: transparent !important;
            border-color: transparent !important;
        }
        header h2 a {
            color: #fbd38d !important;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.9) !important;
        }
        @endif

        /* Quill Snow compact toolbar for table rows */
        .ql-toolbar.ql-snow {
            border: none;
            border-bottom: 1px solid #e5e7eb;
            padding: 4px 6px;
            background: #f9fafb;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .ql-container.ql-snow {
            border: none;
            font-size: 13px;
            min-height: 40px;
        }
        .ql-editor {
            min-height: 40px;
            padding: 6px 8px;
        }
        .ql-editor.ql-blank::before {
            left: 8px;
        }
        /* Wrap the editor+toolbar in a border */
        .quill-wrapper .ql-toolbar,
        .quill-wrapper .ql-container {
            display: block;
        }
        .quill-wrapper {
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            background: white;
        }
        .ql-snow .ql-toolbar button {
            width: 22px;
            height: 22px;
        }
        .ql-snow .ql-toolbar .ql-formats {
            margin-right: 6px;
        }
    </style>
    <script>
        window.refreshPwaApp = function(btn) {
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-wait');
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-spin');
                }
            }
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistrations().then(function(registrations) {
                    for (let reg of registrations) { reg.update(); }
                }).catch(function(e) {});
            }
            setTimeout(function() {
                try {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('_pwa_refresh', Date.now().toString());
                    window.location.replace(currentUrl.toString());
                } catch(e) {
                    window.location.reload();
                }
            }, 200);
        };
    </script>
    @stack('head')
</head>

<body class="{{ ($isHalloween && $isStaffUser) ? 'bg-[#f4effa]' : 'bg-gray-100' }} text-gray-800" x-data="{ mobileSidebarOpen: false }">

    <div class="flex h-screen overflow-hidden">
        <!-- Desktop Sidebar -->
        @unless(View::hasSection('no_sidebar'))
        <aside class="w-64 {{ $isHalloween ? 'bg-[#12081d]' : 'bg-dark' }} text-white flex-shrink-0 hidden md:flex flex-col relative overflow-hidden">
            @if($isHalloween)
                <!-- Bottom Haunted House & Pumpkins illustration pinned at bottom -->
                <div class="absolute bottom-0 left-0 right-0 h-[460px] pointer-events-none opacity-80 select-none z-0"
                     style="background: url('{{ asset('images/halloween/sidebar-bottom.png') }}') bottom center / cover no-repeat;">
                </div>
            @endif

            <div class="p-4 flex items-center justify-center h-20 border-b {{ $isHalloween ? 'border-orange-500/20' : 'border-gray-700' }} relative z-10 overflow-hidden">
                @if($isHalloween)
                    <!-- Spiderweb in top-left corner -->
                    <img src="{{ asset('images/halloween/spiderweb.svg') }}" class="absolute top-0 left-0 w-16 h-16 pointer-events-none opacity-65 z-10" alt="spiderweb">
                    <!-- Realistic Peeking Ghost from top-left corner -->
                    <div class="absolute top-0 left-0 pointer-events-none z-20 animate-ghost-peek">
                        <img src="{{ asset('images/halloween/realistic-ghost.png') }}" class="w-12 h-auto max-h-18 select-none pointer-events-none" alt="ghost">
                    </div>
                    <!-- Hanging spider on thread -->
                    <div class="absolute top-0 right-4 pointer-events-none animate-spider-swing z-10">
                        <img src="{{ asset('images/halloween/hanging-spider.svg') }}" class="w-6 h-9 opacity-90" alt="spider">
                    </div>
                @endif
                <img src="{{ asset('images/logo_loops_light.png') }}" alt="Creative Kiwi" class="h-12 w-auto relative z-10">
            </div>

            <nav class="flex-1 px-2 py-4 space-y-2 overflow-y-auto relative z-10">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center px-4 py-3 rounded-md transition {{ request()->routeIs('dashboard') ? $desktopActiveLink : $desktopInactiveLink }}">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('petty-cash.index') }}"
                    class="flex items-center px-4 py-3 rounded-md transition {{ request()->routeIs('petty-cash.*') ? $desktopActiveLink : $desktopInactiveLink }}">
                    <i class="fas fa-wallet w-6"></i>
                    <span>Petty Cash</span>
                </a>

                @if(auth()->check() && !in_array(auth()->user()->role, ['Staff', 'HR Admin']))
                    <a href="{{ route('customers.index') }}"
                        class="flex items-center px-4 py-3 rounded-md transition {{ request()->routeIs('customers.*') ? $desktopActiveLink : $desktopInactiveLink }}">
                        <i class="fas fa-users w-6"></i>
                        <span>Customers</span>
                    </a>
                    <a href="{{ route('deals.index') }}"
                        class="flex items-center px-4 py-3 rounded-md transition {{ request()->routeIs('deals.*') ? $desktopActiveLink : $desktopInactiveLink }}">
                        <i class="fas fa-funnel-dollar w-6"></i>
                        <span>Deals</span>
                    </a>
                    <a href="{{ route('jobs.index') }}"
                        class="flex items-center px-4 py-3 rounded-md transition {{ request()->routeIs('jobs.*') ? $desktopActiveLink : $desktopInactiveLink }}">
                        <i class="fas fa-briefcase w-6"></i>
                        <span>Jobs</span>
                    </a>
                    <a href="{{ route('estimates.index') }}"
                        class="flex items-center px-4 py-3 rounded-md transition {{ (request()->routeIs('estimates.*') && request('from') !== 'invoice') ? $desktopActiveLink : $desktopInactiveLink }}">
                        <i class="fas fa-file-invoice w-6"></i>
                        <span>Estimates</span>
                    </a>
                    <a href="{{ route('invoices.index') }}"
                        class="flex items-center px-4 py-3 rounded-md transition {{ (request()->is('invoices*') || request('from') === 'invoice') ? $desktopActiveLink : $desktopInactiveLink }}">
                        <i class="fas fa-file-invoice-dollar mr-3 w-5"></i> Invoices
                    </a>
                    <a href="{{ route('reports.index') }}"
                        class="flex items-center px-4 py-3 rounded-md transition {{ request()->routeIs('reports.*') ? $desktopActiveLink : $desktopInactiveLink }}">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span>Reports</span>
                    </a>

                    @if(auth()->check() && auth()->user()->hasAdminPrivileges())
                        <a href="{{ route('users.index') }}"
                            class="flex items-center px-4 py-3 rounded-md transition {{ request()->is('users*') ? $desktopActiveLink : $desktopInactiveLink }}">
                            <i class="fas fa-users-cog mr-3 w-5"></i> Users
                        </a>

                        <a href="{{ route('activities.index') }}"
                            class="flex items-center px-4 py-3 rounded-md transition {{ request()->routeIs('activities.*') ? $desktopActiveLink : $desktopInactiveLink }}">
                            <i class="fas fa-history mr-3 w-5"></i> Activity Log
                        </a>

                        <a href="{{ route('settings.index') }}"
                            class="flex items-center px-4 py-3 rounded-md transition {{ request()->is('settings*') ? $desktopActiveLink : $desktopInactiveLink }}">
                            <i class="fas fa-cog mr-3 w-5"></i> Settings
                        </a>
                    @endif
                @endif
            </nav>

            <div class="px-3 pb-3 relative z-10">
                @if($isHalloween)
                    <button type="button" class="pwa-install-btn flex w-full items-center justify-between px-3 py-2.5 bg-gradient-to-r from-purple-800 via-orange-600 to-orange-500 hover:opacity-95 text-white text-xs font-bold rounded-xl shadow-md border border-orange-400/30 transition-all active:scale-95" title="Install App">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-download text-xs text-orange-200"></i>
                            <span>Install App</span>
                        </div>
                        <img src="{{ asset('images/halloween/pumpkin.svg') }}" class="h-5 w-auto" alt="pumpkin">
                    </button>
                @else
                    <button type="button" class="pwa-install-btn flex w-full items-center justify-center gap-2 px-3 py-2 bg-gradient-to-r from-brand-purple to-brand-pink hover:opacity-90 text-white text-xs font-bold rounded-lg shadow-sm transition-all" title="Install App">
                        <i class="fas fa-download"></i>
                        <span>Install App</span>
                    </button>
                @endif
            </div>

            <div class="p-4 border-t {{ $isHalloween ? 'border-orange-500/20 bg-[#0d0515]/70' : 'border-gray-700' }} relative z-10">
                <div class="flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name={{ Auth::user()->name ?? 'Admin' }}&background=random"
                        alt="User" class="w-8 h-8 rounded-full {{ $isHalloween ? 'border border-orange-400' : '' }}">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate {{ $isHalloween ? 'text-orange-100' : 'text-white' }}">{{ Auth::user()->name ?? 'Admin User' }}</p>
                        <p class="text-xs truncate {{ $isHalloween ? 'text-orange-200/50' : 'text-gray-400' }}">{{ Auth::user()->email ?? 'admin@example.com' }}</p>
                    </div>
                    <div class="flex items-center space-x-1">
                        <button type="button" onclick="document.getElementById('changePasswordModal').classList.remove('hidden')" class="{{ $isHalloween ? 'text-orange-300 hover:text-white' : 'text-gray-400 hover:text-white' }} p-1" title="Change Password">
                            <i class="fas fa-key"></i>
                        </button>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="{{ $isHalloween ? 'text-orange-300 hover:text-white' : 'text-gray-400 hover:text-white' }} p-1" title="Logout">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Mobile Drawer Sidebar (Sliding) -->
        <div x-show="mobileSidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm md:hidden" 
             @click="mobileSidebarOpen = false" 
             style="display: none;"></div>

        <aside x-show="mobileSidebarOpen"
               x-transition:enter="transition ease-in-out duration-300 transform"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in-out duration-300 transform"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full"
               class="fixed inset-y-0 left-0 z-50 w-72 {{ $isHalloween ? 'bg-[#12081d]' : 'bg-dark' }} text-white flex flex-col md:hidden shadow-2xl relative overflow-hidden"
               style="display: none;">

            @if($isHalloween)
                <div class="absolute bottom-0 left-0 right-0 h-[400px] pointer-events-none opacity-80 select-none z-0"
                     style="background: url('{{ asset('images/halloween/sidebar-bottom.png') }}') bottom center / cover no-repeat;">
                </div>
            @endif

            <div class="p-4 flex items-center justify-between h-20 border-b {{ $isHalloween ? 'border-orange-500/20' : 'border-gray-700' }} relative z-10 overflow-hidden">
                @if($isHalloween)
                    <!-- Spiderweb in top-left corner -->
                    <img src="{{ asset('images/halloween/spiderweb.svg') }}" class="absolute top-0 left-0 w-14 h-14 pointer-events-none opacity-50 z-10" alt="spiderweb">
                    <!-- Realistic Peeking Ghost -->
                    <div class="absolute top-0 left-0 pointer-events-none z-20 animate-ghost-peek">
                        <img src="{{ asset('images/halloween/realistic-ghost.png') }}" class="w-10 h-auto max-h-16 select-none pointer-events-none" alt="ghost">
                    </div>
                @endif
                <div class="flex items-center gap-2">
                    @if($isHalloween)
                        <img src="{{ asset('images/halloween/pumpkin.svg') }}" class="w-6 h-auto" alt="pumpkin">
                    @endif
                    <img src="{{ asset('images/logo_loops_light.png') }}" alt="Creative Kiwi" class="h-10 w-auto">
                </div>
                <button @click="mobileSidebarOpen = false" class="text-gray-400 hover:text-white p-2 rounded-lg focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-2 overflow-y-auto relative z-10">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('dashboard') ? $mobileActiveLink : $mobileInactiveLink }}">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('petty-cash.index') }}"
                    class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('petty-cash.*') ? $mobileActiveLink : $mobileInactiveLink }}">
                    <i class="fas fa-wallet w-6"></i>
                    <span>Petty Cash</span>
                </a>

                @if(auth()->check() && !in_array(auth()->user()->role, ['Staff', 'HR Admin']))
                    <a href="{{ route('customers.index') }}"
                        class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('customers.*') ? $mobileActiveLink : $mobileInactiveLink }}">
                        <i class="fas fa-users w-6"></i>
                        <span>Customers</span>
                    </a>
                    <a href="{{ route('deals.index') }}"
                        class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('deals.*') ? $mobileActiveLink : $mobileInactiveLink }}">
                        <i class="fas fa-funnel-dollar w-6"></i>
                        <span>Deals</span>
                    </a>
                    <a href="{{ route('jobs.index') }}"
                        class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('jobs.*') ? $mobileActiveLink : $mobileInactiveLink }}">
                        <i class="fas fa-briefcase w-6"></i>
                        <span>Jobs</span>
                    </a>
                    <a href="{{ route('estimates.index') }}"
                        class="flex items-center px-4 py-3 rounded-lg transition {{ (request()->routeIs('estimates.*') && request('from') !== 'invoice') ? $mobileActiveLink : $mobileInactiveLink }}">
                        <i class="fas fa-file-invoice w-6"></i>
                        <span>Estimates</span>
                    </a>
                    <a href="{{ route('invoices.index') }}"
                        class="flex items-center px-4 py-3 rounded-lg transition {{ (request()->is('invoices*') || request('from') === 'invoice') ? $mobileActiveLink : $mobileInactiveLink }}">
                        <i class="fas fa-file-invoice-dollar mr-3 w-5"></i> Invoices
                    </a>
                    <a href="{{ route('reports.index') }}"
                        class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('reports.*') ? $mobileActiveLink : $mobileInactiveLink }}">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span>Reports</span>
                    </a>

                    @if(auth()->check() && auth()->user()->hasAdminPrivileges())
                        <a href="{{ route('users.index') }}"
                            class="flex items-center px-4 py-3 rounded-lg transition {{ request()->is('users*') ? $mobileActiveLink : $mobileInactiveLink }}">
                            <i class="fas fa-users-cog mr-3 w-5"></i> Users
                        </a>

                        <a href="{{ route('activities.index') }}"
                            class="flex items-center px-4 py-3 rounded-lg transition {{ request()->routeIs('activities.*') ? $mobileActiveLink : $mobileInactiveLink }}">
                            <i class="fas fa-history mr-3 w-5"></i> Activity Log
                        </a>

                        <a href="{{ route('settings.index') }}"
                            class="flex items-center px-4 py-3 rounded-lg transition {{ request()->is('settings*') ? $mobileActiveLink : $mobileInactiveLink }}">
                            <i class="fas fa-cog mr-3 w-5"></i> Settings
                        </a>
                    @endif
                @endif
            </nav>

            <div class="px-3 pb-3 relative z-10">
                @if($isHalloween)
                    <button type="button" class="pwa-install-btn flex w-full items-center justify-between px-4 py-3 bg-gradient-to-r from-purple-800 via-orange-600 to-orange-500 text-white text-sm font-bold rounded-xl shadow-md active:scale-95 transition-all" title="Install App">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-download text-base"></i>
                            <span>Install Creative Kiwi App</span>
                        </div>
                        <img src="{{ asset('images/halloween/pumpkin.svg') }}" class="h-6 w-auto" alt="pumpkin">
                    </button>
                @else
                    <button type="button" class="pwa-install-btn flex w-full items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-brand-purple to-brand-pink text-white text-sm font-bold rounded-xl shadow-md active:scale-95 transition-all" title="Install App">
                        <i class="fas fa-download text-base"></i>
                        <span>Install Creative Kiwi App</span>
                    </button>
                @endif
            </div>

            <div class="p-4 border-t {{ $isHalloween ? 'border-orange-500/20 bg-[#0d0515]/70' : 'border-gray-700 bg-gray-900/50' }} relative z-10">
                <div class="flex items-center space-x-3">
                    <img src="https://ui-avatars.com/api/?name={{ Auth::user()->name ?? 'Admin' }}&background=random"
                        alt="User" class="w-10 h-10 rounded-full {{ $isHalloween ? 'border-2 border-orange-400' : 'border-2 border-brand-pink/50' }}">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold {{ $isHalloween ? 'text-orange-100' : 'text-white' }} truncate">{{ Auth::user()->name ?? 'Admin User' }}</p>
                        <p class="text-xs {{ $isHalloween ? 'text-orange-200/50' : 'text-gray-400' }} truncate">{{ Auth::user()->email ?? 'admin@example.com' }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2 {{ $isHalloween ? 'text-orange-300 hover:text-white' : 'text-gray-400 hover:text-white' }} rounded-lg" title="Logout">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>
        @endunless

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm relative z-40 h-20 flex-shrink-0 flex items-center {{ $isHalloween ? 'border-b border-orange-500/20' : 'border-b border-gray-200' }}"
                @if($isHalloween)
                    style="background: url('{{ asset('images/halloween/header-banner.jpg') }}') center / cover no-repeat;"
                @endif>
                <div class="flex items-center justify-between px-4 sm:px-6 w-full">
                    <div class="flex items-center flex-1 mr-4 min-w-0">
                        @unless(View::hasSection('no_sidebar'))
                        <button @click="mobileSidebarOpen = !mobileSidebarOpen" 
                                class="md:hidden {{ $isHalloween ? 'text-white hover:text-orange-300 hover:bg-white/10' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }} p-2 rounded-lg focus:outline-none transition-colors mr-2">
                            <i class="fas fa-bars text-lg"></i>
                        </button>
                        @endunless
                        <h2 class="text-lg sm:text-xl font-extrabold {{ $isHalloween ? 'text-white drop-shadow-[0_2px_4px_rgba(0,0,0,0.95)]' : 'text-gray-800' }} truncate flex items-center gap-2">
                            @yield('header')
                        </h2>
                    </div>
                    @auth
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <button type="button" 
                                onclick="refreshPwaApp(this)" 
                                class="pwa-refresh-btn px-3 py-1.5 {{ $isHalloween ? 'bg-white hover:bg-slate-50 text-slate-700 border border-purple-200/50 shadow-sm' : 'bg-gray-100/90 hover:bg-gray-200 text-gray-700' }} text-xs font-bold rounded-lg transition-all inline-flex items-center gap-1.5 shadow-2xs active:scale-95 cursor-pointer" 
                                title="Refresh Page">
                            <i class="fas fa-arrows-rotate text-[#8035ca] text-xs sm:text-sm"></i>
                            <span class="hidden sm:inline">Refresh</span>
                        </button>

                        @if($isHalloween)
                            <button type="button" class="pwa-install-btn inline-flex px-3 py-1.5 bg-gradient-to-r from-[#7020eb] to-[#ea580c] hover:opacity-95 text-white text-xs font-bold rounded-lg shadow-sm active:scale-95 transition-all items-center gap-1.5 border border-orange-400/40" title="Install Creative Kiwi">
                                <i class="fas fa-download text-xs text-orange-200"></i>
                                <span>Install</span>
                                <img src="{{ asset('images/halloween/pumpkin.svg') }}" class="w-4 h-4 ml-0.5 inline-block" alt="pumpkin">
                            </button>
                        @else
                            <button type="button" class="pwa-install-btn inline-flex px-2.5 py-1.5 sm:px-3 sm:py-1.5 bg-gradient-to-r from-brand-purple to-brand-pink text-white text-xs font-bold rounded-lg shadow-sm hover:opacity-90 active:scale-95 transition-all items-center gap-1.5" title="Install Creative Kiwi">
                                <i class="fas fa-download text-xs"></i>
                                <span>Install</span>
                            </button>
                        @endif

                        <button type="button" onclick="document.getElementById('changePasswordModal').classList.remove('hidden')" class="px-3 py-1.5 {{ $isHalloween ? 'bg-white hover:bg-slate-50 text-slate-700 border border-purple-200/50 shadow-sm' : 'bg-gray-100/90 hover:bg-gray-200 text-gray-700' }} text-xs font-bold rounded-lg transition-colors inline-flex items-center gap-1.5 shadow-sm" title="Change Password">
                            <i class="fas fa-key text-[#8035ca]"></i>
                            <span class="hidden sm:inline">Change Password</span>
                        </button>

                        <div x-data="{ 
                            open: false, 
                            unreadCount: {{ auth()->check() ? auth()->user()->unreadNotifications->count() : 0 }},
                            markRead() {
                                this.open = !this.open;
                                if (this.open && this.unreadCount > 0) {
                                    fetch('{{ route('notifications.markAsRead') }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                        },
                                    }).then(() => {
                                        this.unreadCount = 0;
                                        // Optionally dim unread items immediately
                                        document.querySelectorAll('.notification-item-unread').forEach(el => {
                                            el.classList.remove('bg-blue-50', 'notification-item-unread');
                                            el.classList.add('opacity-60');
                                        });
                                    });
                                }
                            }
                        }" class="relative">
                        <button @click="markRead()"
                            class="relative p-2 {{ $isHalloween ? 'text-slate-700 bg-white/85 hover:bg-white border border-purple-200/50 shadow-sm rounded-lg' : 'text-gray-500 hover:text-gray-700' }} focus:outline-none transition-colors">
                            <i class="fas fa-bell text-lg"></i>
                            <template x-if="unreadCount > 0">
                                <span
                                    class="absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 {{ $isHalloween ? 'bg-orange-500' : 'bg-red-600' }} rounded-full"
                                    x-text="unreadCount">
                                </span>
                            </template>
                        </button>

                        <!-- Notification Dropdown -->
                        <div x-show="open" @click.away="open = false"
                            class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-2xl overflow-hidden z-50 border border-gray-200"
                            style="display: none;">
                            <div class="py-2">
                                 <div class="px-4 py-2 border-b border-gray-200 text-sm font-semibold text-gray-700 flex justify-between items-center">
                                    <span>Notifications</span>
                                    @if(auth()->check() && auth()->user()->unreadNotifications->count() > 0)
                                        <button onclick="markAllAsRead()" class="text-xs text-brand-blue hover:underline">Mark all as read</button>
                                    @endif
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    @forelse((auth()->check() ? auth()->user()->notifications->take(10) : []) as $notification)
                                        <a href="{{ isset($notification->data['deal_id']) ? route('deals.index') : (isset($notification->data['petty_cash_id']) ? (auth()->user()->role === 'Staff' ? route('dashboard') : route('petty-cash.index')) : (isset($notification->data['request_id']) ? route('customers.requests.review', $notification->data['request_id']) : (isset($notification->data['customer_id']) ? route('customers.edit', $notification->data['customer_id']) : (isset($notification->data['invoice_id']) ? route('invoices.show', $notification->data['invoice_id']) : '#')))) }}"
                                            class="block px-4 py-3 hover:bg-gray-50 transition duration-150 ease-in-out border-b border-gray-100 last:border-b-0 {{ $notification->read_at ? 'opacity-60' : 'bg-blue-50 notification-item-unread' }}">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $notification->data['message'] ?? 'New Notification' }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </p>
                                        </a>
                                    @empty
                                        <div class="px-4 py-3 text-sm text-gray-500 text-center">
                                            No notifications
                                        </div>
                                    @endforelse
                                </div>
                                <div class="border-t border-gray-200 bg-gray-50 px-4 py-2 text-center">
                                    <a href="#" class="text-xs font-medium text-brand-blue hover:text-brand-purple">View all</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endauth
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto {{ ($isHalloween && $isStaffUser) ? 'bg-[#0c0316] text-gray-100' : 'bg-gray-100 text-gray-800' }} p-4 sm:p-6">
                @if(session('success'))
                    <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-xs flex items-center justify-between" role="alert">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                            <div>
                                <strong class="font-bold">Success!</strong>
                                <span class="block sm:inline ml-1 text-sm">{{ session('success') }}</span>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('[role=alert]').remove()" class="text-emerald-500 hover:text-emerald-700 p-1 cursor-pointer">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-xs flex items-center justify-between" role="alert">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-exclamation-circle text-rose-500 text-lg"></i>
                            <div>
                                <strong class="font-bold">Error!</strong>
                                <span class="block sm:inline ml-1 text-sm">{{ session('error') }}</span>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('[role=alert]').remove()" class="text-rose-500 hover:text-rose-700 p-1 cursor-pointer">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-4 bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-r-xl shadow-xs flex items-center justify-between" role="alert">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-exclamation-triangle text-amber-500 text-lg"></i>
                            <div>
                                <strong class="font-bold">Notice:</strong>
                                <span class="block sm:inline ml-1 text-sm">{{ session('warning') }}</span>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('[role=alert]').remove()" class="text-amber-500 hover:text-amber-700 p-1 cursor-pointer">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                @endif

                @if(session('info'))
                    <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-r-xl shadow-xs flex items-center justify-between" role="alert">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                            <div>
                                <strong class="font-bold">Info:</strong>
                                <span class="block sm:inline ml-1 text-sm">{{ session('info') }}</span>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('[role=alert]').remove()" class="text-blue-500 hover:text-blue-700 p-1 cursor-pointer">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
    <script>
        function markAllAsRead() {
            fetch('{{ route('notifications.markAsRead') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Clear dark mode preference if previously set
        localStorage.removeItem('staff_dark_mode');
        document.body.classList.remove('dark');

        // Global Toast Notification Helper
        window.showToast = function(message, type = 'success') {
            if (typeof Swal !== 'undefined') {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 6000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                Toast.fire({
                    icon: type,
                    title: message
                });
            } else {
                alert(message);
            }
        };

        // Auto-trigger Toast on Session Flash Messages
        @if(session('error'))
            window.showToast({!! json_encode(session('error')) !!}, 'error');
        @elseif(session('warning'))
            window.showToast({!! json_encode(session('warning')) !!}, 'warning');
        @elseif(session('success'))
            window.showToast({!! json_encode(session('success')) !!}, 'success');
        @elseif(session('info'))
            window.showToast({!! json_encode(session('info')) !!}, 'info');
        @endif

        // Global handler to disable submit buttons and show loading spinner on form submission
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    if (e.defaultPrevented) return;

                    var submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                    submitBtns.forEach(function(btn) {
                        if (btn.disabled) return;
                        btn.disabled = true;
                        btn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                        if (!btn.querySelector('.fa-spinner')) {
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Processing...';
                        }
                    });
                });
            });
        });

        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>

    @if(auth()->check() && auth()->user()->isITAdmin())
    <script>
        function openRevertInvoiceModal(url, invoiceNum, estimateRef) {
            const refText = estimateRef ? ` (Estimate: <span class="font-semibold text-brand-purple">${estimateRef}</span>)` : '';
            Swal.fire({
                title: '<div class="text-lg font-bold text-gray-800 flex items-center justify-center gap-2"><i class="fas fa-undo-alt text-amber-500"></i> Revert Invoice</div>',
                html: `
                    <div class="text-left text-sm text-gray-700 mt-2 space-y-4">
                        <p class="leading-relaxed">
                            Select the target state to revert Invoice <strong class="text-gray-900 font-bold">#${invoiceNum}</strong>${refText}:
                        </p>
                        <div class="space-y-3">
                            <label class="flex items-start p-3.5 bg-amber-50/70 border-2 border-amber-300 rounded-xl cursor-pointer hover:bg-amber-100/60 transition-colors">
                                <input type="radio" name="swal_target_state" value="ready_to_invoice" checked class="mt-1 text-amber-600 focus:ring-amber-500">
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-gray-900">
                                        <i class="fas fa-file-invoice mr-1.5 text-amber-600"></i> Ready to Invoice State
                                    </span>
                                    <span class="block text-xs text-gray-600 mt-0.5 leading-normal">
                                        Restores estimate to <strong>Ready to Invoice</strong> and regenerates the Temporary Invoice (<span class="font-mono text-gray-700">TEMP_XXXXX</span>) for Finance to process.
                                    </span>
                                </div>
                            </label>
                            <label class="flex items-start p-3.5 bg-gray-50 border-2 border-gray-200 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                <input type="radio" name="swal_target_state" value="draft" class="mt-1 text-amber-600 focus:ring-amber-500">
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-gray-900">
                                        <i class="fas fa-file-signature mr-1.5 text-blue-600"></i> Estimate State (Draft / Pending)
                                    </span>
                                    <span class="block text-xs text-gray-600 mt-0.5 leading-normal">
                                        Restores estimate to <strong>Draft / Pending</strong> state, removes temporary invoices, and unlocks estimate for editing.
                                    </span>
                                </div>
                            </label>
                        </div>
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 flex items-start gap-2.5">
                            <i class="fas fa-exclamation-triangle text-red-500 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <strong class="font-semibold">IT Admin Master Action:</strong> This will delete the current final invoice record (#${invoiceNum}) and reset the estimate lifecycle.
                            </div>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-undo mr-1.5"></i> Yes, Revert Invoice',
                confirmButtonColor: '#d97706',
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#6b7280',
                focusConfirm: false,
                preConfirm: () => {
                    const selected = document.querySelector('input[name="swal_target_state"]:checked');
                    return selected ? selected.value : 'ready_to_invoice';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const targetState = result.value;
                    Swal.fire({
                        title: 'Reverting Invoice...',
                        text: 'Please wait while the invoice is being reverted.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            target_state: targetState
                        })
                    })
                    .then(response => response.json().then(data => ({ ok: response.ok, data })))
                    .then(({ ok, data }) => {
                        if (ok && data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Invoice Reverted',
                                text: data.message,
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = data.redirect || window.location.href;
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Failed to revert invoice.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error reverting invoice:', error);
                        Swal.fire('Error', 'An unexpected error occurred while reverting invoice.', 'error');
                    });
                }
            });
        }
    </script>
    @endif

    <!-- Change Password Modal (Accessible to All Logged In Users) -->
    <div id="changePasswordModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm {{ (isset($errors) && ($errors->has('current_password') || $errors->has('password'))) ? '' : 'hidden' }} overflow-y-auto h-full w-full z-50 p-4 flex items-center justify-center">
        <div class="relative my-auto p-6 border w-full max-w-md shadow-2xl rounded-2xl bg-white">
            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-key text-brand-purple mr-2"></i> Change Password
                </h3>
                <button onclick="document.getElementById('changePasswordModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <form action="{{ route('password.change') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Current Password *</label>
                    <div class="relative">
                        <input type="password" name="current_password" id="current_password_input" required class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-purple focus:ring-brand-purple pr-10">
                        <button type="button" onclick="togglePasswordVisibility('current_password_input', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">New Password * (Min. 8 characters)</label>
                    <div class="relative">
                        <input type="password" name="password" id="new_password_input" minlength="8" required class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-purple focus:ring-brand-purple pr-10">
                        <button type="button" onclick="togglePasswordVisibility('new_password_input', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Confirm New Password *</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="confirm_password_input" minlength="8" required class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-purple focus:ring-brand-purple pr-10">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password_input', this)" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-100">
                    <button type="button" onclick="document.getElementById('changePasswordModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 text-gray-800 text-xs font-semibold rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-gradient-to-r from-brand-purple to-brand-pink text-white text-xs font-bold rounded-lg hover:opacity-90 shadow-md flex items-center">
                        <i class="fas fa-save mr-1.5"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Halloween Celebration Effects (Smiling Pumpkin, Swarming Bats & Trick or Treat) -->
    @include('partials.halloween-celebration')

    <!-- PWA Install Prompts & Controller -->
    @include('partials.pwa-install')
</body>

</html>