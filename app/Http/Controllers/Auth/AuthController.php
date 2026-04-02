<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientUserResource;
use App\Http\Resources\CompanyUserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

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

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|exists:users,email',
            'password' => 'required|string',
            'user_type' => 'required|string'
        ]);

        $user = User::where('email', $request->email)
            ->where('type', $request->user_type)
            ->first();

        if (!Hash::check($request->password, $user->password)) {
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
        $user = User::with([
            'events' => function ($query) {
                $query->where('checked', 0);
            }
        ])->find($request->user()->id);

        if ($user->type === 'company') {
            return response([
                'user' => new CompanyUserResource($user)
            ]);
        }

        return response([
            'user' => new ClientUserResource($user)
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
    public function register(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
            'user_type' => 'required|string',
        ]);

        if ($validation->fails()) {
            return response([
                'errors' => $validation->errors()
            ], 300);
        }

        $hashedPassword = Hash::make($request->password);

        $user = User::create([
            'type' => $request->user_type,
            'name' => $request->name,
            'email' => $request->email,
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
