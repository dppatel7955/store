<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    public static function get(): array
    {
        return session()->get('cart', []);
    }

    public static function add(int $productId, int $quantity = 1): void
    {
        $cart = self::get();
        $product = Product::find($productId);
        
        if (!$product) {
            return;
        }

        $price = $product->sale_price ?? $product->price;

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
            $cart[$productId]['total'] = $cart[$productId]['quantity'] * $price;
        } else {
            $cart[$productId] = [
                'id' => $productId,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => (float)$price,
                'image' => $product->images[0] ?? null,
                'quantity' => $quantity,
                'total' => (float)($price * $quantity)
            ];
        }

        session()->put('cart', $cart);
    }

    public static function update(int $productId, int $quantity): void
    {
        $cart = self::get();
        if (isset($cart[$productId])) {
            if ($quantity <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId]['quantity'] = $quantity;
                $cart[$productId]['total'] = $quantity * $cart[$productId]['price'];
            }
            session()->put('cart', $cart);
        }
    }

    public static function remove(int $productId): void
    {
        $cart = self::get();
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            session()->put('cart', $cart);
        }
    }

    public static function getCount(): int
    {
        $count = 0;
        foreach (self::get() as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }

    public static function getSubtotal(): float
    {
        $subtotal = 0;
        foreach (self::get() as $item) {
            $subtotal += $item['total'];
        }
        return (float)$subtotal;
    }

    public static function clear(): void
    {
        session()->forget('cart');
    }
}
