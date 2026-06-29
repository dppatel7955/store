<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-900 selection:bg-indigo-500 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $slug = request()->route('slug');
        $seoTitle = $title ?? match(true) {
            request()->is('shop/*') && $slug => 'Saffron Store - ' . ucwords(str_replace('-', ' ', $slug)),
            request()->is('shop') => 'Shop Premium Hardware Components - Saffron Store',
            request()->is('checkout') => 'Checkout - Saffron Store',
            request()->is('orders*') => 'My Orders - Saffron Store',
            request()->is('login') => 'Login - Saffron Store',
            default => 'Saffron Store - High-Performance Computer Hardware'
        };

        $seoDesc = $metaDescription ?? match(true) {
            request()->is('shop/*') && $slug => 'Buy ' . ucwords(str_replace('-', ' ', $slug)) . ' at Saffron Store. Check reviews, stock levels, and technical specs.',
            request()->is('shop') => 'Browse high-quality graphics cards, CPUs, RAMs, SSDs, and motherboard parts with quick local shipping.',
            request()->is('checkout') => 'Secure checkout portal for Saffron Store purchases.',
            request()->is('orders*') => 'Track your placed orders, invoices, and shipment status.',
            default => 'Discover premium, high-performance computer hardware components at Saffron Store. Fast deliveries, secure payments, and expert local customer service.'
        };
    @endphp

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">

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
                        <a href="{{ url('')}}" class="flex items-center gap-2">
                            <span class="text-xl sm:text-2xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-600 via-purple-650 to-pink-600 bg-clip-text text-transparent">
                                SAFFRON STORE
                            </span>
                        </a>
                    </div>
                </div>

                <!-- Navigation Links (Desktop) -->
                <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-650">
                    <a href="{{ url('')}}" class="hover:text-indigo-600 transition-colors {{ request()->is('/') ? 'text-indigo-600 font-bold' : '' }}">Home</a>
                    <a href="{{ url('shop')}}" class="hover:text-indigo-600 transition-colors {{ request()->is('shop*') ? 'text-indigo-600 font-bold' : '' }}">Shop</a>
                </nav>

                <!-- Search Bar (Desktop/Tablet) -->
                <div class="flex-1 max-w-md mx-4 hidden sm:block">
                    <form action="{{ url('shop')}}" method="GET" class="relative">
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
                            <a href="{{ url('admin')}}" class="text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-full px-3 py-1 hover:bg-indigo-100 transition md:inline-flex hidden">
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
                                <a href="{{ url('orders')}}" class="block px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition">My Orders</a>
                                <form method="POST" action="/logout" class="block w-full text-left">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-slate-50 hover:text-slate-900 transition">Sign Out</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ url('login')}}" class="text-sm font-bold text-slate-650 hover:text-indigo-600 transition hidden md:block">Sign In</a>
                    @endauth

                    <!-- Dynamic Livewire Cart Trigger -->
                    @livewire('cart-trigger')

                </div>
            </div>
        </div>

        <!-- Mobile Menu (collapsible) -->
        <div 
            x-show="mobileMenuOpen" 
            x-transition
            class="md:hidden border-t border-slate-200 bg-white px-4 pt-2 pb-4 space-y-2 shadow-inner"
            style="display: none;"
        >
            <a href="{{ url('')}}" class="block px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition {{ request()->is('/') ? 'bg-indigo-50/50 text-indigo-650' : '' }}">Home</a>
            <a href="{{ url('shop')}}" class="block px-3 py-2 rounded-xl text-sm font-bold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition {{ request()->is('shop*') ? 'bg-indigo-50/50 text-indigo-650' : '' }}">Shop</a>
            
            <!-- Mobile Search Bar (under sm screen width) -->
            <div class="px-3 py-1 sm:hidden">
                <form action="{{ url('shop')}}" method="GET" class="relative">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}"
                        placeholder="Search products..." 
                        class="w-full bg-slate-100 border border-slate-200 rounded-full py-1.5 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600 transition"
                    />
                    <span class="absolute left-3.5 top-2.5 text-slate-450">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                </form>
            </div>

            <!-- Admin Link inside Mobile Menu -->
            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ url('admin')}}" class="block px-3 py-2 rounded-xl text-sm font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition">
                        Admin Panel
                    </a>
                @endif
            @endauth

            <!-- User Menu inside Mobile Menu -->
            @auth
                <div class="border-t border-slate-100 pt-3 px-3">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shadow">
                            {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-800">{{ auth()->user()->name }}</div>
                            <div class="text-[10px] text-slate-500">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <a href="{{ url('orders')}}" class="block py-1.5 text-xs font-semibold text-slate-650 hover:text-slate-905">My Orders</a>
                        <form method="POST" action="/logout" class="block w-full">
                            @csrf
                            <button type="submit" class="block w-full text-left py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700">Sign Out</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="border-t border-slate-100 pt-3 px-3">
                    <a href="{{ url('login')}}" class="block text-center rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 py-2 text-xs font-bold text-white shadow hover:from-indigo-650 hover:to-purple-705 transition">Sign In</a>
                </div>
            @endauth
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
                            <li><a href="{{ url('shop?category=' . $item->slug) }}" class="hover:text-indigo-400 transition">{{$item->name}}</a></li>
                        @endforeach
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
