<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::get('/shop', function () {
    return view('pages.shop');
})->name('shop');

Route::get('/shop/{slug}', function ($slug) {
    return view('pages.detail', compact('slug'));
})->name('shop.detail');

Route::get('/login', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return view('pages.login');
})->name('login');

Route::get('/register', function () {
    if (auth()->check()) {
        return redirect()->route('home');
    }
    return view('pages.register');
})->name('register');

Route::get('/verify-email', function () {
    return view('pages.verify-email');
})->name('verify-email');

Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('home');
});

Route::get('/checkout', function () {
    if (!auth()->check()) {
        return redirect()->route('login', ['redirect' => 'checkout']);
    }
    return view('pages.checkout');
})->middleware('auth')->name('checkout');

Route::get('/order-success/{id}', function ($id) {
    return view('pages.success', compact('id'));
})->middleware('auth')->name('order-success');

Route::get('/orders', function () {
    return view('pages.orders');
})->middleware('auth')->name('orders');

Route::get('/orders/{id}', function ($id) {
    return view('pages.order-detail', compact('id'));
})->middleware('auth')->name('orders.detail');

Route::get('/admin/login', function () {
    if (auth()->check() && auth()->user()->is_admin) {
        return redirect()->route('admin.dashboard');
    }
    return view('pages.admin.login');
})->name('admin.login');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', function () {
        return view('pages.admin.dashboard');
    })->name('admin.dashboard');
    Route::get('/admin/categories', function () {
        return view('pages.admin.categories');
    })->name('admin.categories');
    Route::get('/admin/brands', function () {
        return view('pages.admin.brands');
    })->name('admin.brands');
    Route::get('/admin/products', function () {
        return view('pages.admin.products');
    })->name('admin.products');
    Route::get('/admin/orders', function () {
        return view('pages.admin.orders');
    })->name('admin.orders');
    Route::get('/admin/orders/{id}', function ($id) {
        return view('pages.admin.order-detail', compact('id'));
    })->name('admin.orders.detail');
    Route::get('/admin/users', function () {
        return view('pages.admin.users');
    })->name('admin.users');
    Route::get('/admin/email-setup', function () {
        return view('pages.admin.email-setup');
    })->name('admin.email-setup');
    Route::get('/admin/home-settings', function () {
        return view('pages.admin.home-settings');
    })->name('admin.home-settings');
    Route::get('/admin/orders/{id}/invoice', function ($id) {
        $order = \App\Models\Order::with(['items.product'])->findOrFail($id);
        return view('pages.admin.invoice', compact('order'));
    })->name('admin.orders.invoice');
});

