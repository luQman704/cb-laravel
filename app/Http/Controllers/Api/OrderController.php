<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::with('items')
            ->where('customer_id', $request->user('sanctum')->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::with('items')
            ->where('customer_id', $request->user('sanctum')->id)
            ->findOrFail($id);

        return response()->json(new OrderResource($order));
    }
}
