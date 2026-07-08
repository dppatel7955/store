<x-layouts.app>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Header -->
        <div class="text-center md:text-left mb-8">
            <nav class="flex text-xs text-slate-400 font-bold mb-3 uppercase tracking-wider gap-2">
                <a href="/" class="hover:text-indigo-650 transition">Home</a>
                <span>/</span>
                @if($category->parent)
                    <a href="{{ route('categories.detail', ['slug' => $category->parent->slug]) }}" class="hover:text-indigo-650 transition">{{ $category->parent->name }}</a>
                    <span>/</span>
                @endif
                <span class="text-slate-600">{{ $category->name }}</span>
            </nav>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $category->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $category->description ?? 'Browse the subcategories under ' . $category->name }}</p>
        </div>

        <!-- Subcategories Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($category->children->where('is_active', true) as $child)
                <a href="{{ route('categories.detail', ['slug' => $child->slug]) }}" class="group relative block overflow-hidden rounded-2xl bg-white border border-slate-200 p-6 text-center hover:border-indigo-500 hover:shadow-md transition duration-300">
                    <div class="h-24 w-24 mx-auto mb-4 overflow-hidden rounded-full border border-slate-100 group-hover:scale-105 transition duration-300">
                        <img src="{{ $child->image }}" loading="lazy" decoding="async" alt="{{ $child->name }}" class="h-full w-full object-cover">
                    </div>
                    <h3 class="text-base font-bold text-slate-800 group-hover:text-indigo-600 transition">{{ $child->name }}</h3>
                    <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ $child->description ?? 'Browse items' }}</p>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
