<?php

namespace App\Actions\List;

use App\Http\Resources\ClientListResource;
use App\Models\ItensList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexListAction
{
    public function execute(Request $request)
    {
        $user = Auth::user();

        $itensList = ItensList::where('user_id', $user->id)
            ->with(['products', 'listProducts.product.unity', 'listProducts.product.category'])
            ->latest()
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 15);

        return ClientListResource::collection($itensList);
    }
}
