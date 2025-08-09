<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

        // Laravel usa diretamente o método getEmailForVerification()
        // sem trim, sem lowercase — apenas conversão para string
        Log::alert("aaaaaa");
        $expectedHash = sha1($user->getEmailForVerification());

        if (! hash_equals($expectedHash, $hash)) {
            Log::error("Hash inválido. Esperado: $expectedHash, recebido: $hash");
            abort(403, 'Hash inválido');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            Log::info("Email verificado para usuário ID {$user->id}");
        }

        return redirect(config('app.frontend_url') . '/email-confirmed');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|exists:users,email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function user(Request $request)
    {
        return $request->user();
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
