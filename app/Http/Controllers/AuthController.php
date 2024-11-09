<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request) {
        $details = $request->validate([
            'name' => 'required|string|max:255',
            // 'phone' => 'required|integer|size:10',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/|confirmed|min:8'
        ]);

        $user = User::create($details);

        $token = $user->createToken($request->email);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'message' => 'Entered email or password is incorrect'
            ];
        }   
        
        $token = $user->createToken($user->email);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function logout(Request $request) {
        $request->user()->tokens()->delete();

        return ['message' => 'You are logged out.'];
    }
}
