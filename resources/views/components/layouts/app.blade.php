<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-900 selection:bg-indigo-500 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon Links -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    @php
        $slug = request()->route('slug');
        $product = null;
        if (request()->routeIs('shop.detail') && $slug) {
            $product = \App\Models\Product::where('slug', $slug)->first();
        }

        $seoTitle = $title ?? match(true) {
            $product !== null => $product->name . ' - Saffron Store',
            request()->is('shop') => 'Shop Premium Products - Saffron Store',
            request()->is('checkout') => 'Checkout - Saffron Store',
            request()->is('orders*') => 'My Orders - Saffron Store',
            request()->is('login') => 'Login - Saffron Store',
            default => 'Saffron Store - Premium Online Shopping Hub'
        };

        $seoDesc = $metaDescription ?? match(true) {
            $product !== null => $product->short_description ? strip_tags($product->short_description) : 'Buy ' . $product->name . ' at Saffron Store. Check reviews, stock levels, and specs.',
            request()->is('shop') => 'Browse premium products, hot deals, and exclusive catalogs with quick shipping.',
            request()->is('checkout') => 'Secure checkout portal for Saffron Store purchases.',
            request()->is('orders*') => 'Track your placed orders, invoices, and shipment status.',
            default => 'Discover premium products at Saffron Store. Fast deliveries, secure payments, and expert local customer service.'
        };

        $seoImage = '';
        if ($product !== null && is_array($product->images) && count($product->images) > 0) {
            $firstImg = $product->images[0];
            $seoImage = str_starts_with($firstImg, 'http') ? $firstImg : asset($firstImg);
        } else {
            try {
                $settingsPath = storage_path('app/home_settings.json');
                if (file_exists($settingsPath)) {
                    $settings = json_decode(file_get_contents($settingsPath), true);
                    if (!empty($settings['banner_image'])) {
                        $seoImage = asset($settings['banner_image']);
                    }
                }
            } catch (\Throwable $e) {}
        }
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">

    <!-- Open Graph / Facebook / Instagram / WhatsApp / Messages -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    @if(!empty($seoImage))
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:secure_url" content="{{ $seoImage }}">
        <meta property="og:image:type" content="image/jpeg">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endif

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    @if(!empty($seoImage))
        <meta name="twitter:image" content="{{ $seoImage }}">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Style Override -->
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        button{
            cursor: pointer;
        }
    </style>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @livewireStyles
</head>
<body class="h-full antialiased bg-slate-50 text-slate-900 flex flex-col min-h-screen">
    
    <!-- Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                
                <div class="flex items-center gap-4">
                    <!-- Hamburger Toggle Button -->
                    <button 
                        @click="mobileMenuOpen = !mobileMenuOpen" 
                        type="button" 
                        class="md:hidden inline-flex items-center justify-center p-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 focus:outline-none transition" 
                        aria-label="Toggle mobile menu"
                    >
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" style="display: none;" />
                        </svg>
                    </button>

                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('home') }}" class="flex items-center gap-2">
                            <span class="text-xl sm:text-2xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-600 via-purple-650 to-pink-600 bg-clip-text text-transparent">
                                SAFFRON STORE
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-650">
                    <a href="{{ route('home') }}" class="hover:text-indigo-600 transition-colors {{ request()->is('/') ? 'text-indigo-600 font-bold' : '' }}">Home</a>
                    <a href="{{ route('shop') }}" class="hover:text-indigo-600 transition-colors {{ request()->is('shop*') ? 'text-indigo-600 font-bold' : '' }}">Shop</a>
                </nav>

                <!-- Search Bar (Desktop/Tablet) -->
                <div class="flex-1 max-w-md mx-4 hidden sm:block">
                    <form action="{{ route('shop') }}" method="GET" class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Search products, brands, categories..." 
                            aria-label="Search products, brands, and categories"
                            class="w-full bg-slate-100 border border-slate-200 rounded-full py-1.5 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                        />
                        <span class="absolute left-3.5 top-2.5 text-slate-450">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                    </form>
                </div>

                <!-- Right Actions -->
                <div class="flex items-center space-x-6">
                    <!-- Admin Panel Link (Desktop only) -->
                    @auth
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-full px-3 py-1 hover:bg-indigo-100 transition md:inline-flex hidden">
                                Admin Panel
                            </a>
                        @endif
                    @endauth

                    <!-- User Account Dropdown (Desktop only) -->
                    @auth
                        <div class="relative hidden md:block" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-1 hover:text-indigo-600 transition text-slate-800 font-bold">
                                <span class="text-sm font-semibold">{{ auth()->user()->name }}</span>
                                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <!-- Dropdown Menu -->
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-xl py-1 text-slate-700 text-sm z-50">
                                <div class="px-4 py-2 border-b border-slate-100 text-xs text-slate-400">
                                    Signed in as<br><span class="font-bold text-slate-800">{{ auth()->user()->email }}</span>
                                </div>
                                <a href="{{ route('orders') }}" class="block px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition">My Orders</a>
                                <form method="POST" action="{{ route('logout') }}" class="block w-full text-left">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition">Sign Out</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-bold text-slate-650 hover:text-indigo-600 transition hidden md:block">Sign In</a>
                    @endauth

                    <!-- Dynamic Livewire Cart Trigger -->
                    @livewire('cart-trigger')

                </div>
            </div>

            <!-- Mobile Search Bar (Directly inside main nav header on mobile) -->
            <div class="pb-3 block sm:hidden">
                <form action="{{ route('shop') }}" method="GET" class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search products, brands, categories..." 
                        class="w-full bg-slate-100 border border-slate-200 rounded-full py-1.5 pl-10 pr-4 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-650 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    <span class="absolute left-3.5 top-2.5 text-slate-450">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                </form>
            </div>
        </div>

        <!-- Mobile Left Sidebar Drawer Toggle -->
        <div 
            x-show="mobileMenuOpen" 
            x-effect="document.body.classList.toggle('overflow-hidden', mobileMenuOpen)"
            class="fixed inset-0 z-50 md:hidden" 
            style="display: none;"
            role="dialog" 
            aria-modal="true"
        >
            <!-- Backdrop Overlay -->
            <div 
                x-show="mobileMenuOpen"
                x-transition:enter="transition-opacity ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="mobileMenuOpen = false"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
            ></div>

            <!-- Drawer Panel -->
            <div 
                x-show="mobileMenuOpen"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
                class="fixed inset-y-0 left-0 w-full max-w-xs bg-white shadow-2xl flex flex-col justify-between z-50 border-r border-slate-200"
            >
                <div class="flex-grow bg-white py-6 px-5 space-y-6">
                    <!-- Drawer Header -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <span class="text-base font-extrabold tracking-wider bg-gradient-to-r from-indigo-600 via-purple-650 to-pink-600 bg-clip-text text-transparent">
                            SAFFRON STORE
                        </span>
                        <button 
                            @click="mobileMenuOpen = false" 
                            type="button" 
                            class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition"
                            aria-label="Close menu"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>



                    <!-- Navigation Links -->
                    <div class="space-y-1">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2 pl-2">Navigation</span>
                        
                        <a href="{{ route('home') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition {{ request()->is('/') ? 'bg-indigo-50/60 text-indigo-650' : '' }}">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Home
                        </a>
                        <a href="{{ route('shop') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition {{ request()->is('shop*') ? 'bg-indigo-50/60 text-indigo-650' : '' }}">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Shop Catalog
                        </a>
                        <a href="{{ route('shipping-policy') }}" @click="mobileMenuOpen = false" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition {{ request()->is('shipping-policy') ? 'bg-indigo-50/60 text-indigo-650' : '' }}">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Shipping Policy
                        </a>
                    </div>
                </div>

                <!-- Footer User Area inside Drawer -->
                <div class="border-t border-slate-100 bg-white p-5 space-y-4">
                    @auth
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold shadow-md">
                                {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                            </div>
                            <div class="flex-grow min-w-0">
                                <div class="text-xs font-bold text-slate-800 truncate">{{ auth()->user()->name }}</div>
                                <div class="text-[10px] text-slate-450 truncate">{{ auth()->user()->email }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 pt-2">
                            @if(auth()->user()->is_admin)
                                <a href="{{ route('admin.dashboard') }}" @click="mobileMenuOpen = false" class="flex items-center justify-center gap-2 rounded-xl bg-indigo-50 border border-indigo-250 py-2 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                    </svg>
                                    Admin Dashboard
                                </a>
                            @endif

                            <a href="{{ route('orders') }}" @click="mobileMenuOpen = false" class="flex items-center justify-center gap-2 rounded-xl bg-white border border-slate-200 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                My Orders
                            </a>

                            <form method="POST" action="{{ route('logout') }}" class="block w-full">
                                @csrf
                                <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-xl bg-rose-50 border border-rose-100 py-2 text-xs font-bold text-rose-600 hover:bg-rose-100 transition">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 01-3-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="space-y-2">
                            <p class="text-[10px] text-slate-400 text-center font-medium">Log in to track orders, manage addresses, and checkout quickly.</p>
                            <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="block text-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-2.5 text-xs font-bold text-white shadow hover:from-indigo-650 hover:to-purple-705 transition">
                                Sign In
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
</header>

    <!-- Main Content -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 border-t border-slate-800 py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-bold bg-gradient-to-r from-indigo-400 to-pink-400 bg-clip-text text-transparent mb-4">SAFFRON STORE</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Experience the next generation of e-commerce. Fast checkout, premium products, and curated quality tags.
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">Shop</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        @foreach (\App\Models\Category::where('is_active',1)->inRandomOrder()->limit(5)->get() as $item)    
                            <li><a href="{{ route('shop', ['category' => $item->slug]) }}" class="hover:text-indigo-400 transition">{{$item->name}}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">Support</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="{{ route('orders') }}" class="hover:text-indigo-400 transition">Track Order</a></li>
                        <li><a href="{{ route('shipping-policy') }}" class="hover:text-indigo-400 transition">Shipping Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="{{ route('privacy-policy') }}" class="hover:text-indigo-400 transition">Privacy Policy</a></li>
                        <li><a href="{{ route('terms-of-service') }}" class="hover:text-indigo-400 transition">Terms of Service</a></li>
                        <li><a href="{{ route('payment-methods') }}" class="hover:text-indigo-400 transition">Payment Methods</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800/60 mt-8 pt-8 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} SAFFRON STORE E-Commerce. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- Dynamic Cart Drawer Slide-over -->
    @livewire('cart-drawer')

    @livewireScripts
    
    <!-- SweetAlert2 Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('swal', event => {
                const detail = event.detail;
                
                const title = detail.title || (Array.isArray(detail) ? detail[0]?.title : 'Notification');
                const text = detail.text || (Array.isArray(detail) ? detail[0]?.text : '');
                const icon = detail.icon || (Array.isArray(detail) ? detail[0]?.icon : 'success');
                const isToast = detail.toast !== false && (Array.isArray(detail) ? detail[0]?.toast !== false : true);

                if (isToast) {
                    Swal.fire({
                        toast: true,
                        title: title,
                        position: 'top-end',
                        text: text,
                        icon: icon,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    });
                } else {
                    Swal.fire({
                        toast: false,
                        title: title,
                        text: text,
                        icon: icon,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#4f46e5'
                    });
                }
            });

            @if (session()->has('success'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    title: 'Success!',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
            @endif

            @if (session()->has('error'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    title: 'Error!',
                    text: "{{ session('error') }}",
                    icon: 'error',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
            @endif
            
            @if (session()->has('review_success'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    title: 'Thank You!',
                    text: "{{ session('review_success') }}",
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
            @endif
        });
    </script>
</body>
</html>
