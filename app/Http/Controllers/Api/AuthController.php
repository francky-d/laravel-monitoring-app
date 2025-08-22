<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Traits\HasApiResponses;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 * 
 * APIs for user authentication including registration, login, logout, and profile management.
 */
class AuthController extends Controller
{
    use HasApiResponses;
    /**
     * Register a new user
     * 
     * Create a new user account and return an authentication token.
     * 
     * @unauthenticated
     * 
     * @bodyParam name string required The user's full name. Example: John Doe
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password (minimum 8 characters). Example: secretpassword
     * @bodyParam password_confirmation string required Password confirmation. Example: secretpassword
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "User registered successfully",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "token": "1|abc123..."
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->createdResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'User registered successfully');
    }

    /**
     * Login user
     * 
     * Authenticate a user with email and password, returning an authentication token.
     * 
     * @unauthenticated
     * 
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: secretpassword
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Login successful",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "token": "1|abc123..."
     *   }
     * }
     * 
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "email": ["The provided credentials are incorrect."]
     *   }
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Logout user
     * 
     * Revoke the current authentication token, effectively logging out the user.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Logged out successfully",
     *   "data": null
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user
     * 
     * Retrieve the profile information of the currently authenticated user.
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "User profile retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   }
     * }
     */
    public function user(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UserResource($request->user()),
            'User profile retrieved successfully'
        );
    }
}
