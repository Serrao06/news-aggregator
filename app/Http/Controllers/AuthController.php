<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse {
        $request->validate([
            'name' => 'required|string|max:255',
            // 'phone' => 'required|integer|size:10',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/|confirmed|min:8'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        if ($user) {
            $token = $user->createToken($request->email);

            return response()->json([
                'message' => 'Registration Successful.',
                'user' => $user,
                'token' => $token->plainTextToken
            ], 201);
        }
        return response()->json([
            'message' => 'Something went worng! Please try later.',
        ], 500);
    }

    public function login(Request $request) : JsonResponse {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Entered email or password is incorrect'
            ],401);
        }   
        
        $token = $user->createToken($user->email);

        return response()->json(
            [
            'message' => 'Login Successful.',
            'user' => $user,
            'token_type' => 'Bearer',
            'token' => $token->plainTextToken
        ], 200);
    }

    public function logout(Request $request): JsonResponse {
        $user = User::where('id', $request->user()->id)->first();
        
        if ($user) {
            $request->user()->tokens()->delete();
            return response()->json(
                ['message' => 'You are logged out.'],200 
            );
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }
}
