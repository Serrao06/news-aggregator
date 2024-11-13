<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use OpenApi\Annotations as OA;
use App\Models\User;


class PasswordResetController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/forgot-password",
     *     summary="Send reset password link",
     *     description="Sends a password reset link to the provided email if it's registered.",
     *     operationId="resetLink",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", example="user@example.com", description="The email of the user requesting the password reset link")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email sent successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email sent Successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Email not found or invalid.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Make sure entered email is correct user@example.com")
     *         )
     *     )
     * )
     */
    public function resetLink(Request $request): JsonResponse
    {

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

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Reset user password",
     *     description="Resets the user's password using the provided token, email, and new password.",
     *     operationId="resetPassword",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", description="The token sent to the user's email for password reset"),
     *             @OA\Property(property="email", type="string", format="email", description="The email of the user requesting the password reset"),
     *             @OA\Property(property="password", type="string", description="The new password (must meet security requirements)"),
     *             @OA\Property(property="password_confirmation", type="string", description="Confirmation of the new password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password Updated Successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to update password.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to update password! Please try after some time")
     *         )
     *     )
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {

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
                'message' => "Password Updated Successfully"
            ], 200)
            : response()->json([
                'message' => "Failed to update password! Please try after some time"
            ], 500);
    }
}
