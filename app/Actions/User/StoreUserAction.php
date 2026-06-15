<?php

namespace App\Actions\User;

use App\Http\Requests\User\UserStoreRequest;
use App\Services\User\StoreUserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StoreUserAction
{
    public function __construct(private StoreUserService $storeUserService)
    {
    }

    public function execute(UserStoreRequest $request)
    {
        try {
            $user = $this->storeUserService->execute(
                $request->validated(),
                Auth::id()
            );

            return response([
                'message' => 'Usuário criado com sucesso!',
                'user' => $user,
            ]);
        } catch (\Throwable $th) {
            Log::error('Erro ao criar usuário', [
                'error' => $th->getMessage(),
            ]);

            return response([
                'error' => $th->getMessage(),
            ]);
        }
    }
}
