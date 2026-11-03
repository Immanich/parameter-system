<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class DriverAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'tricycle_number' => ['required', 'string', 'max:50', 'unique:drivers,tricycle_number'],
            'contact_number' => ['required', 'string', 'max:30', 'unique:drivers,contact_number'],
            'email' => ['nullable', 'email', 'max:255', 'unique:drivers,email'],
            'password' => ['required', 'string', Password::min(6)],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $driver = Driver::create($data);

        return response()->json([
            'token' => $driver->createToken($data['device_name'] ?? 'mobile')->plainTextToken,
            'driver' => $driver,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $driver = Driver::where('tricycle_number', $data['login'])
            ->orWhere('contact_number', $data['login'])
            ->first();

        if (! $driver || ! Hash::check($data['password'], $driver->password)) {
            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        return response()->json([
            'token' => $driver->createToken($data['device_name'] ?? 'mobile')->plainTextToken,
            'driver' => $driver,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['driver' => $request->user()]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
