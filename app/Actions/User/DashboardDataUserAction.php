<?php

namespace App\Actions\User;

use App\Http\Resources\ClientDashboardResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardDataUserAction
{
    public function execute(Request $request)
    {
        if (!$request->user()->isClient()) {
            return response([
                'message' => 'Não autorizado',
            ], 403);
        }

        $user = User::with('recentActivity')->findOrFail(Auth::id());

        return response([
            'dashboardData' => new ClientDashboardResource($user),
        ]);
    }
}
