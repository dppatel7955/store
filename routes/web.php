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

Route::get('/categories/{slug}', function ($slug) {
    $category = \App\Models\Category::with('children')->where('slug', $slug)->firstOrFail();
    if ($category->children->where('is_active', true)->isNotEmpty()) {
        return view('pages.category-detail', compact('category'));
    }
    return redirect()->route('shop', ['category' => $category->slug]);
})->name('categories.detail');

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
})->name('logout');

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
    Route::get('/admin/coupons', function () {
        return view('pages.admin.coupons');
    })->name('admin.coupons');
    Route::get('/admin/brands', function () {
        return view('pages.admin.brands');
    })->name('admin.brands');
    Route::get('/admin/products', function () {
        return view('pages.admin.products');
    })->name('admin.products');
    Route::get('/admin/products/create', function () {
        return view('pages.admin.products-create');
    })->name('admin.products.create');
    Route::get('/admin/products/{id}/edit', function ($id) {
        return view('pages.admin.products-edit', compact('id'));
    })->name('admin.products.edit');
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
    Route::get('/admin/payment-methods', function () {
        return view('pages.admin.payment-methods');
    })->name('admin.payment-methods');
    Route::get('/admin/orders/{id}/invoice', function ($id) {
        $order = \App\Models\Order::with(['items.product', 'paymentMethodConfig'])->findOrFail($id);
        return view('pages.admin.invoice', compact('order'));
    })->name('admin.orders.invoice');
    Route::get('/admin/stock', function () {
        return view('pages.admin.stock');
    })->name('admin.stock');
});

Route::get('/privacy-policy', function () {
    return view('pages.privacy-policy');
})->name('privacy-policy');

Route::get('/terms-of-service', function () {
    return view('pages.terms-of-service');
})->name('terms-of-service');

Route::get('/payment-methods', function () {
    $paymentMethods = \App\Models\PaymentMethod::active()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

    return view('pages.payment-methods', compact('paymentMethods'));
})->name('payment-methods');

Route::get('/shipping-policy', function () {
    return view('pages.shipping-policy');
})->name('shipping-policy');

