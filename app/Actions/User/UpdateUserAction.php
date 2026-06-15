<?php

namespace App\Actions\User;

use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use App\Services\User\UpdateUserService;
use Illuminate\Support\Facades\Auth;

class UpdateUserAction
{
    public function __construct(private UpdateUserService $updateUserService)
    {
    }

    public function execute(UserUpdateRequest $request, User $user)
    {
        $currentUser = Auth::user();

        if (!$currentUser->isAdmin() && $user->id !== $currentUser->id) {
            return response([
                'message' => 'Não autorizado',
            ], 403);
        }

        $user = $this->updateUserService->execute(
            $user,
            $request->validated(),
            Auth::id()
        );

        return response([
            'message' => 'Usuário editado com sucesso!',
            'user' => new AdminUserResource($user),
        ]);
    }
}
