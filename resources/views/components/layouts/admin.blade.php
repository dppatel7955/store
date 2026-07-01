<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-900 selection:bg-indigo-500 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'SAFFRON STORE Admin Panel' }}</title>

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
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    @livewireStyles
</head>
<body class="h-full antialiased bg-slate-50 text-slate-900" x-data="{ sidebarOpen: false }">
    
    <div class="min-h-full">
        <!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->
        <div x-show="sidebarOpen" class="relative z-50 lg:hidden" role="dialog" aria-modal="true" style="display: none;">
            <div x-show="sidebarOpen" 
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm"></div>

            <div class="fixed inset-0 flex">
                <div x-show="sidebarOpen" 
                     x-transition:enter="transition ease-in-out duration-300 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-300 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="relative mr-16 flex w-full max-w-xs flex-1 transform bg-white border-r border-slate-200"
                     @click.away="sidebarOpen = false">
                    
                    <!-- Close button -->
                    <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                        <button type="button" @click="sidebarOpen = false" class="-m-2.5 p-2.5 text-slate-500 hover:text-slate-900">
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Sidebar content for mobile -->
                    <div class="flex grow flex-col gap-y-5 overflow-y-auto px-6 pb-4 bg-white">
                        <div class="flex h-16 shrink-0 items-center">
                            <span class="text-2xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-600 via-purple-650 to-pink-600 bg-clip-text text-transparent">
                                SAFFRON STORE
                            </span>
                        </div>
                        <nav class="flex flex-1 flex-col">
                            <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                <li>
                                    <ul role="list" class="-mx-2 space-y-1">
                                        @include('components.layouts.admin-nav')
                                    </ul>
                                </li>
                                <li class="mt-auto">
                                    <div class="flex items-center gap-x-4 px-2 py-3 text-sm font-semibold leading-6 text-slate-700 border-t border-slate-100">
                                        <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-indigo-650 font-bold border border-slate-200">
                                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                                        </div>
                                        <span class="truncate text-slate-800 font-bold">{{ auth()->user()->name ?? 'Administrator' }}</span>
                                    </div>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-72 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white border-r border-slate-200 px-6 pb-4 shadow-sm">
                <div class="flex h-16 shrink-0 items-center justify-between">
                    <span class="text-2xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-600 via-purple-650 to-pink-600 bg-clip-text text-transparent">
                        SAFFRON STORE
                    </span>
                    <a href="{{url('')}}" class="text-xs font-bold text-slate-550 hover:text-indigo-650 border border-slate-200 rounded-full px-2 py-0.5 transition">
                        Shop
                    </a>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                @include('components.layouts.admin-nav')
                            </ul>
                        </li>
                        <li class="mt-auto">
                            <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                                <div class="flex items-center gap-x-3 text-sm font-semibold leading-6 text-slate-700">
                                    <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-indigo-600 to-purple-700 flex items-center justify-center text-white font-bold shadow-md">
                                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="truncate text-slate-800 text-xs font-bold leading-tight">{{ auth()->user()->name ?? 'Admin User' }}</span>
                                        <span class="truncate text-slate-500 text-[10px]">{{ auth()->user()->email ?? 'admin@example.com' }}</span>
                                    </div>
                                </div>
                                <form method="POST" action="/logout">
                                    @csrf
                                    <button type="submit" class="text-slate-450 hover:text-rose-600 p-1.5 rounded-lg hover:bg-slate-50 transition-colors" title="Sign Out">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="lg:pl-72">
            <!-- Mobile Header -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-slate-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 lg:hidden">
                <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-slate-500 hover:text-slate-900">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                @php
                    $pageTitle = $title ?? match(true) {
                        request()->is('admin/categories*') => 'Categories',
                        request()->is('admin/brands*') => 'Brands',
                        request()->is('admin/products*') => 'Products',
                        request()->is('admin/orders*') => 'Orders',
                        request()->is('admin/users*') => 'Users',
                        default => 'Dashboard'
                    };
                @endphp
                <div class="flex-1 text-sm font-semibold leading-6 text-slate-900">{{ $pageTitle }}</div>
                <div class="flex items-center gap-x-4">
                    <a href="{{ url('') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900">View Shop</a>
                </div>
            </div>

            <!-- Page Content Wrapper -->
            <main class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScripts
    
    <!-- SweetAlert2 Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.addEventListener('swal', event => {
                const detail = event.detail;
                
                const title = detail.title || (Array.isArray(detail) ? detail[0]?.title : 'Notification');
                const text = detail.text || (Array.isArray(detail) ? detail[0]?.text : '');
                const icon = detail.icon || (Array.isArray(detail) ? detail[0]?.icon : 'success');

                Swal.fire({
                    title: title,
                    toast: true,
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
            });

            @if (session()->has('success'))
                Swal.fire({
                    title: 'Success!',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
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
                    title: 'Error!',
                    text: "{{ session('error') }}",
                    icon: 'error',
                    toast: true,
                    position: 'top-end',
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
