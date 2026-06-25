<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home');
});

Route::get('/shop', function () {
    return view('pages.shop');
});

Route::get('/shop/{slug}', function ($slug) {
    return view('pages.detail', compact('slug'));
});

Route::get('/login', function () {
    if (auth()->check()) {
        return redirect('/');
    }
    return view('pages.login');
})->name('login');

Route::post('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/');
});

Route::get('/checkout', function () {
    if (!auth()->check()) {
        return redirect('/login?redirect=checkout');
    }
    return view('pages.checkout');
})->middleware('auth');

Route::get('/order-success/{id}', function ($id) {
    return view('pages.success', compact('id'));
})->middleware('auth');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', function () {
        return view('pages.admin.dashboard');
    });
    Route::get('/admin/categories', function () {
        return view('pages.admin.categories');
    });
    Route::get('/admin/brands', function () {
        return view('pages.admin.brands');
    });
    Route::get('/admin/products', function () {
        return view('pages.admin.products');
    });
    Route::get('/admin/orders', function () {
        return view('pages.admin.orders');
    });
    Route::get('/admin/orders/{id}', function ($id) {
        return view('pages.admin.order-detail', compact('id'));
    });
    Route::get('/admin/users', function () {
        return view('pages.admin.users');
    });
});

