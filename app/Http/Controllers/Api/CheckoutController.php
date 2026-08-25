<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingMethod;
use App\Models\TaxRate;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user('sanctum');

        $data = $request->validate([
            'shipping.firstname'   => 'required|string|max:255',
            'shipping.lastname'    => 'required|string|max:255',
            'shipping.address1'    => 'required|string|max:255',
            'shipping.address2'    => 'nullable|string|max:255',
            'shipping.city'        => 'required|string|max:255',
            'shipping.postcode'    => 'required|string|max:20',
            'shipping.country'     => 'required|string|max:100',
            'shipping.phone'       => 'nullable|string|max:30',
            'shipping_method_id'   => 'required|integer|exists:shipping_methods,id',
            'payment_method'       => 'required|string|max:100',
            'guest_email'          => 'nullable|email|required_without:customer',
        ]);

        // Guest checkout requires guest_email when not authenticated
        if (!$customer && empty($data['guest_email'])) {
            return response()->json(['message' => 'guest_email is required for guest checkout.'], 422);
        }

        $cart = $this->cartService->resolveCart($request);
        $cart = $this->cartService->loadCart($cart);

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 422);
        }

        $shippingMethod = ShippingMethod::where('id', $data['shipping_method_id'])
            ->where('active', true)
            ->firstOrFail();

        // Tax rate: use active 15% rate or fallback constant
        $taxRate = TaxRate::where('active', true)->orderBy('rate', 'desc')->first();
        $taxRateValue = $taxRate ? (float) $taxRate->rate / 100 : 0.15;

        // Calculate totals
        $subtotalEx = 0.0;
        foreach ($cart->items as $item) {
            $subtotalEx += $item->quantity * (float) $item->product->price;
        }
        $subtotalEx   = round($subtotalEx, 2);
        $taxAmount    = round($subtotalEx * $taxRateValue, 2);
        $shippingCost = round((float) $shippingMethod->base_price, 2);
        $total        = round($subtotalEx + $taxAmount + $shippingCost, 2);

        $shipping = $data['shipping'];

        $order = DB::transaction(function () use (
            $cart, $customer, $data, $shipping,
            $shippingMethod, $taxRateValue, $subtotalEx,
            $taxAmount, $shippingCost, $total
        ) {
            $order = Order::create([
                'customer_id'          => $customer?->id,
                'guest_email'          => $customer ? null : ($data['guest_email'] ?? null),
                'ship_firstname'       => $shipping['firstname'],
                'ship_lastname'        => $shipping['lastname'],
                'ship_address1'        => $shipping['address1'],
                'ship_address2'        => $shipping['address2'] ?? null,
                'ship_city'            => $shipping['city'],
                'ship_postcode'        => $shipping['postcode'],
                'ship_country'         => $shipping['country'],
                'ship_phone'           => $shipping['phone'] ?? null,
                'shipping_method_id'   => $shippingMethod->id,
                'shipping_method_name' => $shippingMethod->name,
                'status'               => 'pending',
                'subtotal'             => $subtotalEx,
                'tax_amount'           => $taxAmount,
                'shipping_cost'        => $shippingCost,
                'total'                => $total,
                'payment_method'       => $data['payment_method'],
            ]);

            foreach ($cart->items as $item) {
                $product   = $item->product;
                $unitPrice = round((float) $product->price, 2);
                $lineTotal = round($unitPrice * $item->quantity, 2);

                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $product->id,
                    'product_name'      => $product->name,
                    'product_reference' => $product->reference,
                    'quantity'          => $item->quantity,
                    'unit_price'        => $unitPrice,
                    'tax_rate'          => round($taxRateValue * 100, 3),
                    'line_total'        => $lineTotal,
                ]);

                // Decrement stock
                if ($product->stock) {
                    $product->stock->decrement('quantity', $item->quantity);
                }
            }

            // Clear cart
            $cart->items()->delete();

            return $order;
        });

        $order->load('items');

        return response()->json(new OrderResource($order), 201);
    }
}
