<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Loops Integrated</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-fixed" style="background: linear-gradient(135deg, #ff0878 0%, #8035ca 35%, #0057be 70%, #2fc9c3 100%);">
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full bg-white/10 blur-3xl animate-pulse"></div>
        <div class="absolute top-[60%] -right-[5%] w-[30%] h-[30%] rounded-full bg-white/10 blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <div class="bg-white/95 backdrop-blur-md p-10 rounded-2xl shadow-2xl w-full max-w-lg border border-white/20 transform transition-all duration-300 hover:shadow-white/10 text-center relative z-10">
        <div class="flex flex-col items-center mb-6">
            <div class="bg-white p-4 rounded-2xl shadow-sm mb-6">
                <img src="{{ asset('images/logo_loops.png') }}" alt="Loops Integrated" class="h-16 w-auto">
            </div>
            
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center text-blue-500 mb-6">
                <i class="fas fa-search text-3xl"></i>
            </div>
            
            <h1 class="text-4xl font-black text-gray-800 tracking-tight">404</h1>
            <h2 class="text-xl font-bold text-gray-700 mt-2">Page Not Found</h2>
            <p class="text-gray-600 mt-3 font-medium leading-relaxed text-sm">
                The page or record you are looking for does not exist, has been removed, or the link has expired.
            </p>
        </div>

        <div class="border-t border-gray-100 pt-6 flex flex-col sm:flex-row gap-3 items-center justify-center">
            <a href="{{ url('/') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 transition-colors shadow-sm cursor-pointer">
                <i class="fas fa-home mr-2"></i> Return to Dashboard
            </a>
            <button onclick="window.history.back()" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2.5 rounded-xl text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors cursor-pointer">
                <i class="fas fa-arrow-left mr-2"></i> Go Back
            </button>
        </div>

        <div class="mt-8 text-center border-t border-gray-100 pt-6">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-widest">&copy; {{ date('Y') }} Loops Integrated. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
