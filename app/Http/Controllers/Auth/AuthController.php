<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Requests\Auth\ApiRegisterRequest;
use App\Http\Resources\AdminUserResource;
use App\Http\Resources\ClientUserResource;
use App\Http\Resources\CompanyUserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Verify if the email is verified, and verify if it's valid.
     *
     * @param Int $id
     * @param String $hash
     * @return void
     */
    public function verifyEmail(Int $id, String $hash)
    {
        $user = User::findOrFail($id);

        $expectedHash = sha1($user->getEmailForVerification());

        if (! hash_equals($expectedHash, $hash)) {
            Log::error("Hash inválido. Esperado: $expectedHash, recebido: $hash");
            abort(403, 'Hash inválido');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return response([
            'message' => 'success'
        ]);
    }

    public function login(ApiLoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])
            ->where('type', $validated['user_type'])
            ->first();

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        if ($user->type === 'company') {
            $user->load(['companies', 'activeCompanies', 'pendingCompanies', 'events']);

            return response([
                'user' => (new CompanyUserResource($user))->withNotifications()
            ]);
        }

        if ($user->type === 'admin') {
            return response([
                'user' => (new AdminUserResource($user))->withNotifications()
            ]);
        }

        return response([
            'user' => (new ClientUserResource($user))->withNotifications()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }

    public function notice()
    {
        return response([
            'message' => 'Email verification required',
            'verified' => false
        ], 403);
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return void
     */
    public function register(ApiRegisterRequest $request)
    {
        $validated = $request->validated();

        $hashedPassword = Hash::make($validated['password']);

        $user = User::create([
            'type' => $validated['user_type'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $hashedPassword,
        ]);

        event(new Registered($user));

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        return response([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }
}
