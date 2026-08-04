<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     operationId="registerUser",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="User registered successfully."),
     *         @OA\Property(property="user", ref="#/components/schemas/User"),
     *         @OA\Property(property="token", type="string", example="1|token")
     *     )),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('train-booking-app')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'token' => $token,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Log in a user",
     *     operationId="loginUser",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Login successful."),
     *         @OA\Property(property="user", ref="#/components/schemas/User"),
     *         @OA\Property(property="token", type="string", example="1|token")
     *     )),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('train-booking-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'token' => $token,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Log out the authenticated user",
     *     operationId="logoutUser",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Logout successful", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Logout successful.")
     *     ))
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Auth"},
     *     summary="Get the authenticated user profile",
     *     operationId="getCurrentUser",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Authenticated user profile", @OA\JsonContent(
     *         @OA\Property(property="user", ref="#/components/schemas/User")
     *     ))
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'user' => $user,
            'roles' => $user ? $user->getRoleNames() : [],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     tags={"Auth"},
     *     summary="Get the authenticated dashboard payload",
     *     operationId="getDashboard",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(response=200, description="Dashboard data", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Welcome to your dashboard."),
     *         @OA\Property(property="user", ref="#/components/schemas/User")
     *     ))
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Welcome to your dashboard.',
            'user' => $request->user(),
        ]);
    }
}
