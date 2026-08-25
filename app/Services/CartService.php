<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use Illuminate\Http\Request;

class CartService
{
    /**
     * Find or create the cart for the current request.
     * Authenticated users: cart keyed by customer_id.
     * Guests: cart keyed by session_id.
     */
    public function resolveCart(Request $request): Cart
    {
        /** @var Customer|null $customer */
        $customer = $request->user('sanctum');

        if ($customer) {
            $cart = Cart::firstOrCreate(
                ['customer_id' => $customer->id],
                ['session_id' => null]
            );

            // Detach from any stale session reference
            if ($cart->session_id !== null) {
                $cart->session_id = null;
                $cart->save();
            }

            return $cart;
        }

        $sessionId = $request->session()->getId();

        return Cart::firstOrCreate(
            ['session_id' => $sessionId, 'customer_id' => null]
        );
    }

    /**
     * After login: move session cart items into the customer cart and
     * delete the now-orphaned session cart.
     */
    public function mergeSessionCartToCustomer(string $sessionId, Customer $customer): void
    {
        $sessionCart = Cart::where('session_id', $sessionId)
            ->whereNull('customer_id')
            ->first();

        if (!$sessionCart) {
            return;
        }

        $customerCart = Cart::firstOrCreate(
            ['customer_id' => $customer->id],
            ['session_id' => null]
        );

        foreach ($sessionCart->items as $item) {
            $existing = $customerCart->items()
                ->where('product_id', $item->product_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $item->quantity);
            } else {
                CartItem::create([
                    'cart_id'    => $customerCart->id,
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                ]);
            }
        }

        $sessionCart->delete();
    }

    /**
     * Compute cart totals. The cart must have items loaded with their products.
     */
    public function getCartTotals(Cart $cart): array
    {
        $subtotalEx  = 0.0;
        $subtotalInc = 0.0;
        $itemCount   = 0;

        foreach ($cart->items as $item) {
            $priceEx   = (float) $item->product->price;
            $priceInc  = $priceEx * 1.15;
            $qty       = $item->quantity;

            $subtotalEx  += $qty * $priceEx;
            $subtotalInc += $qty * $priceInc;
            $itemCount   += $qty;
        }

        return [
            'subtotal_ex_tax'  => round($subtotalEx, 2),
            'subtotal_inc_tax' => round($subtotalInc, 2),
            'item_count'       => $itemCount,
        ];
    }

    /**
     * Load the cart with all relations needed for display.
     */
    public function loadCart(Cart $cart): Cart
    {
        $cart->load(['items.product.images', 'items.product.stock']);
        return $cart;
    }
}
