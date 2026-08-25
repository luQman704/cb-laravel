<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $cart   = $this->cartService->resolveCart($request);
        $cart   = $this->cartService->loadCart($cart);
        $totals = $this->cartService->getCartTotals($cart);

        return response()->json(new CartResource($cart, $totals));
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::with('stock')->findOrFail($data['product_id']);

        if (!$product->active) {
            return response()->json(['message' => 'Product is not available.'], 422);
        }

        // Stock check
        $stock = $product->stock;
        if ($stock && !$stock->allow_out_of_stock) {
            $cart       = $this->cartService->resolveCart($request);
            $existingQty = $cart->items()->where('product_id', $product->id)->value('quantity') ?? 0;
            $requested   = $existingQty + $data['quantity'];

            if ($requested > $stock->quantity) {
                return response()->json([
                    'message'         => 'Insufficient stock.',
                    'available_stock' => $stock->quantity,
                ], 422);
            }
        }

        $cart = $this->cartService->resolveCart($request);

        $existing = $cart->items()->where('product_id', $product->id)->first();
        if ($existing) {
            $existing->increment('quantity', $data['quantity']);
        } else {
            CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $product->id,
                'quantity'   => $data['quantity'],
            ]);
        }

        $cart   = $this->cartService->loadCart($cart);
        $totals = $this->cartService->getCartTotals($cart);

        return response()->json(new CartResource($cart, $totals), 201);
    }

    public function updateItem(Request $request, int $cartItemId): JsonResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = $this->cartService->resolveCart($request);
        $item = $cart->items()->where('id', $cartItemId)->first();

        if (!$item) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        if ($data['quantity'] === 0) {
            $item->delete();
        } else {
            // Stock check
            $product = Product::with('stock')->find($item->product_id);
            $stock   = $product?->stock;

            if ($stock && !$stock->allow_out_of_stock && $data['quantity'] > $stock->quantity) {
                return response()->json([
                    'message'         => 'Insufficient stock.',
                    'available_stock' => $stock->quantity,
                ], 422);
            }

            $item->update(['quantity' => $data['quantity']]);
        }

        $cart   = $this->cartService->loadCart($cart);
        $totals = $this->cartService->getCartTotals($cart);

        return response()->json(new CartResource($cart, $totals));
    }

    public function removeItem(Request $request, int $cartItemId): JsonResponse
    {
        $cart = $this->cartService->resolveCart($request);
        $item = $cart->items()->where('id', $cartItemId)->first();

        if (!$item) {
            return response()->json(['message' => 'Cart item not found.'], 404);
        }

        $item->delete();

        $cart   = $this->cartService->loadCart($cart);
        $totals = $this->cartService->getCartTotals($cart);

        return response()->json(new CartResource($cart, $totals));
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->resolveCart($request);
        $cart->items()->delete();

        $cart   = $this->cartService->loadCart($cart);
        $totals = $this->cartService->getCartTotals($cart);

        return response()->json(new CartResource($cart, $totals));
    }
}
