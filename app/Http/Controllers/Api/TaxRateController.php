<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use Illuminate\Http\JsonResponse;

class TaxRateController extends Controller
{
    public function index(): JsonResponse
    {
        $rates = TaxRate::where('active', true)->orderBy('rate')->get();

        return response()->json(['data' => $rates]);
    }
}
