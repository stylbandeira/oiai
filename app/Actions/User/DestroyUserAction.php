<?php

namespace App\Actions\User;

use App\Models\User;

class DestroyUserAction
{
    public function execute(User $user)
    {
        $user->load('companies');

        if (count($user->companies)) {
            return response([
                'message' => 'Apague a relação entre usuário e empresa primeiro.',
            ], 400);
        }

        if ($user->type === 'admin') {
            return response([
                'message' => 'Infelizmente não é possível deletar usuários do tipo admin.',
            ], 400);
        }

        if ($user->deleted_at) {
            $user->restore();

            return response([
                'message' => 'Usuário reativado com sucesso!',
            ]);
        }

        $user->delete();

        return response([
            'message' => 'Usuário excluído com sucesso!',
        ]);
    }
}
