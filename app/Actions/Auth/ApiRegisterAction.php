<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class ApiRegisterAction
{
    public function execute(array $data)
    {
        $user = User::create([
            'type' => $data['user_type'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        $user->sendEmailVerificationNotification();

        return response([
            'user' => $user,
            'access_token' => $user->createToken('auth_token', ['*'])->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }
}
