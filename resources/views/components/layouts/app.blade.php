<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-900 selection:bg-indigo-500 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Darshan E-Commerce' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Style Override -->
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @livewireStyles
</head>
<body class="h-full antialiased bg-slate-50 text-slate-900 flex flex-col min-h-screen">
    
    <!-- Navigation Header -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="/" class="flex items-center gap-2">
                        <span class="text-2xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-600 via-purple-650 to-pink-600 bg-clip-text text-transparent">
                            DARSHAN
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-650">
                    <a href="/" class="hover:text-indigo-600 transition-colors {{ request()->is('/') ? 'text-indigo-600 font-bold' : '' }}">Home</a>
                    <a href="/shop" class="hover:text-indigo-600 transition-colors {{ request()->is('shop*') ? 'text-indigo-600 font-bold' : '' }}">Shop</a>
                </nav>

                <!-- Search Bar -->
                <div class="flex-1 max-w-md mx-4 hidden sm:block">
                    <form action="/shop" method="GET" class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ request('search') }}"
                            placeholder="Search products, brands, categories..." 
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
                    <!-- Admin Panel Link (if admin) -->
                    @auth
                        @if(auth()->user()->is_admin)
                            <a href="/admin" class="text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-full px-3 py-1 hover:bg-indigo-100 transition sm:inline-flex hidden">
                                Admin Panel
                            </a>
                        @endif
                    @endauth

                    <!-- User Account Dropdown / Link -->
                    @auth
                        <div class="relative" x-data="{ open: false }">
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
                                <a href="/orders" class="block px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition">My Orders</a>
                                <form method="POST" action="/logout" class="block w-full text-left">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition">Sign Out</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="/login" class="text-sm font-bold text-slate-650 hover:text-indigo-600 transition">Sign In</a>
                    @endauth

                    <!-- Dynamic Livewire Cart Trigger -->
                    @livewire('cart-trigger')

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
                    <h3 class="text-lg font-bold bg-gradient-to-r from-indigo-400 to-pink-400 bg-clip-text text-transparent mb-4">DARSHAN</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Experience the next generation of e-commerce. Fast checkout, premium products, and curated quality tags.
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">Shop</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="/shop?category=mobile-phones" class="hover:text-indigo-400 transition">Smartphones</a></li>
                        <li><a href="/shop?category=laptops" class="hover:text-indigo-400 transition">Laptops</a></li>
                        <li><a href="/shop?category=smart-watches" class="hover:text-indigo-400 transition">Smartwatches</a></li>
                        <li><a href="/shop?category=accessories" class="hover:text-indigo-400 transition">Accessories</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">Support</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="#" class="hover:text-indigo-400 transition">Help Center</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition">Track Order</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition">Returns & Exchanges</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition">Shipping Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-200 uppercase tracking-wider mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm text-slate-400">
                        <li><a href="#" class="hover:text-indigo-400 transition">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-indigo-400 transition">Payment Methods</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-800/60 mt-8 pt-8 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} Darshan E-Commerce. All rights reserved. Built with Laravel + Livewire + Tailwind.
            </div>
        </div>
    </footer>

    <!-- Dynamic Cart Drawer Slide-over -->
    @livewire('cart-drawer')

    @livewireScripts
</body>
</html>
