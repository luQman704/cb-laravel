<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;

class ShippingMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $methods = ShippingMethod::where('active', true)->orderBy('base_price')->get();

        return response()->json(['data' => $methods]);
    }
}
