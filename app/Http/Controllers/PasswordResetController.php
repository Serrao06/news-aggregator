<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\User;


class PasswordResetController extends Controller
{
    public function resetLink(Request $request): JsonResponse {
        
        $request->validate(['email' => 'required|email|max:255']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
                ? response()->json([
                    'message' => "Email sent Successfully",
                ], 200)
                : response()->json([
                    'message' => "Make sure entered email is correct {$request->email}",
                ], 404);
    }

    public function resetPassword(Request $request): JsonResponse {

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{6,}$/|confirmed|min:8',
        ]);


        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ]);
     
                $user->save();
     
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
                ? response()->json([
                    'message' => "Password Updated Successfully"], 200)
                : response()->json([
                    'message' => "Failed to update password! Please try after some time"], 500);
    }
}
