<?php

namespace App\Actions\User;

use App\Http\Resources\AdminUserResource;
use App\Models\User;

class RevertDestroyUserAction
{
    public function execute(User $user)
    {
        if (!$user->deleted_at) {
            return response([
                'message' => 'Usuário não precisa ser reativado.',
            ], 400);
        }

        $user->restore();

        return response([
            'message' => 'Usuário revertido',
            'user' => new AdminUserResource($user),
        ]);
    }
}
