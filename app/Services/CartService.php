<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

class CartService
{
    public static function get(): array
    {
        return session()->get('cart', []);
    }

    public static function add(int $productId, int $quantity = 1, ?int $variantId = null): void
    {
        $cart = self::get();
        $product = Product::find($productId);
        
        if (!$product) {
            return;
        }

        $price = $product->sale_price ?? $product->price;
        $variantName = null;
        $variantImage = null;

        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant) {
                $price = $variant->sale_price ?? $variant->price ?? $price;
                $variantName = $variant->name;
                $variantImage = (is_array($variant->images) && count($variant->images) > 0) ? $variant->images[0] : null;
            }
        }

        $cartKey = $variantId ? "{$productId}-{$variantId}" : (string)$productId;

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
            $cart[$cartKey]['total'] = $cart[$cartKey]['quantity'] * $price;
        } else {
            $cart[$cartKey] = [
                'id' => $productId,
                'variant_id' => $variantId,
                'variant_name' => $variantName,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => (float)$price,
                'image' => $variantImage ?? ($product->images[0] ?? null),
                'quantity' => $quantity,
                'total' => (float)($price * $quantity)
            ];
        }

        session()->put('cart', $cart);
    }

    public static function update(string $cartKey, int $quantity): void
    {
        $cart = self::get();
        if (isset($cart[$cartKey])) {
            if ($quantity <= 0) {
                unset($cart[$cartKey]);
            } else {
                $cart[$cartKey]['quantity'] = $quantity;
                $cart[$cartKey]['total'] = $quantity * $cart[$cartKey]['price'];
            }
            session()->put('cart', $cart);
        }
    }

    public static function remove(string $cartKey): void
    {
        $cart = self::get();
        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
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
