<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Capture session cart ID before authentication changes the session
        $sessionId = $request->session()->getId();

        $customer = Customer::where('email', $data['email'])->first();

        if (!$customer || !Hash::check($data['password'], $customer->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$customer->active) {
            return response()->json(['message' => 'Account is inactive.'], 403);
        }

        // Merge guest session cart into customer cart
        $this->cartService->mergeSessionCartToCustomer($sessionId, $customer);

        $token = $customer->createToken('api-token')->plainTextToken;

        return response()->json([
            'token'    => $token,
            'customer' => new CustomerResource($customer),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'firstname'             => 'required|string|max:255',
            'lastname'              => 'required|string|max:255',
            'email'                 => 'required|email|unique:customers,email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $sessionId = $request->session()->getId();

        $customer = Customer::create([
            'firstname' => $data['firstname'],
            'lastname'  => $data['lastname'],
            'email'     => $data['email'],
            'password'  => $data['password'], // Model casts to hashed
            'active'    => true,
        ]);

        // Merge guest session cart into the new customer cart
        $this->cartService->mergeSessionCartToCustomer($sessionId, $customer);

        $token = $customer->createToken('api-token')->plainTextToken;

        return response()->json([
            'token'    => $token,
            'customer' => new CustomerResource($customer),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(new CustomerResource($request->user('sanctum')));
    }
}
