<?php

namespace App\Actions\List;

use App\Http\Requests\List\ListStoreRequest;
use App\Http\Resources\ClientListResource;
use App\Models\ItensList;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreListAction
{
    public function execute(ListStoreRequest $request)
    {
        $user = Auth::user();

        if ($user->type !== 'client') {
            return response([
                'message' => 'Apenas clientes podem criar listas.',
            ], 403);
        }

        $list = DB::transaction(function () use ($request, $user) {
            $list = ItensList::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'favorite' => false,
                'total' => 0,
            ]);

            $productsWithQuantities = [];
            $productIds = array_column(array_column($request->products, 'product'), 'id');

            foreach ($request->products as $product) {
                $productsWithQuantities[$product['product']['id']] = ['quantity' => $product['quantity']];
            }

            $list->products()->attach($productsWithQuantities);
            Product::whereIn('id', $productIds)->increment('listAdded');

            return $list->load(['products', 'listProducts.product.unity', 'listProducts.product.category']);
        });

        return response([
            'message' => 'Lista criada com sucesso!',
            'list' => new ClientListResource($list),
        ]);
    }
}
