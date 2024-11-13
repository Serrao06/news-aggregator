<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Annotations as OA;
use App\Models\User;

/**
 * @OA\Info(
 *  title="Laravel v11 auth API's",
 *  version="1.0"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: Bearer {token}"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     description="Create a new user with name, email, and password and return a Santum token upon successful registration.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe", description="Full name of the user"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Email of the user"),
     *             @OA\Property(property="password", type="string", format="password", example="Password@123", description="Password for the account"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password@123", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Registration successful."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="your-santum-token-here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The email has already been taken.")
     *                 ),
     *                 @OA\Property(property="password", type="array",
     *                     @OA\Items(type="string", example="The password must be at least 8 characters.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Something went wrong! Please try later.")
     *         )
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
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
                'message' => 'Registration successful.',
                'user' => $user,
                'token' => $token->plainTextToken
            ], 201);
        }
        return response()->json([
            'message' => 'Something went worng! Please try later.',
        ], 500);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login to a user",
     *     description="Login to an existing user with email and password and return a Santum token upon successfull login.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="Email of the user"),
     *             @OA\Property(property="password", type="string", format="password", example="Password@123", description="Password for the user account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login successful."),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com")
     *             ),
     *              @OA\Property(property="token_type", type="string", example="Bearer"),
     *              @OA\Property(property="token", type="string", example="your-santum-token-here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Entered email or password is incorrect")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array",
     *                     @OA\Items(type="string", example="The selected email is invalid.")
     *                 ),
     *                 @OA\Property(property="password", type="array",
     *                     @OA\Items(type="string", example="The password field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Entered email or password is incorrect'
            ], 401);
        }

        $token = $user->createToken($user->email);

        return response()->json(
            [
                'message' => 'Login Successful.',
                'user' => $user,
                'token_type' => 'Bearer',
                'token' => $token->plainTextToken
            ],
            200
        );
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="User logout",
     *     description="Logs out the authenticated user by deleting all tokens.",
     *     tags={"Authentication"},
     *     security={{ "sanctum": {} }},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You are logged out.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $user = User::where('id', $request->user()->id)->first();

        if ($user) {
            $request->user()->tokens()->delete();
            return response()->json(
                ['message' => 'You are logged out.'],
                200
            );
        } else {
            return response()->json(['message' => 'User not found'], 404);
        }
    }
}
