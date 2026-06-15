<?php

namespace App\Actions\Auth;

use App\Http\Resources\AdminUserResource;
use App\Http\Resources\ClientUserResource;
use App\Http\Resources\CompanyUserResource;
use App\Models\User;

class GetAuthenticatedUserAction
{
    public function execute(User $user)
    {
        if ($user->type === 'company') {
            $user->load(['companies', 'activeCompanies', 'pendingCompanies', 'events']);

            return response([
                'user' => (new CompanyUserResource($user))->withNotifications(),
            ]);
        }

        if ($user->type === 'admin') {
            return response([
                'user' => (new AdminUserResource($user))->withNotifications(),
            ]);
        }

        return response([
            'user' => (new ClientUserResource($user))->withNotifications(),
        ]);
    }
}
