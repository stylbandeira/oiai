<?php

namespace App\Actions\User;

use App\Http\Resources\AdminUserResource;
use App\Http\Resources\ClientUserResource;
use App\Http\Resources\CompanyUserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ShowUserAction
{
    public function execute(User $user)
    {
        $currentUser = Auth::user();
        $user->load(['companies', 'pendingCompanies', 'activeCompanies', 'events']);

        if ($currentUser->isAdmin()) {
            return new AdminUserResource($user);
        } else if ($currentUser->isClient() && $user->id === $currentUser->id) {
            return new ClientUserResource($user);
        } else if ($currentUser->isCompany() && $user->id === $currentUser->id) {
            return new CompanyUserResource($user);
        }

        return response([
            'message' => 'Não autorizado',
        ], 403);
    }
}
