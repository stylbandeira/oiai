<?php

namespace App\Actions\FavoriteProducts;

use App\Http\Requests\FavoriteProducts\FavoriteProductRequest;
use App\Models\FavoriteProducts;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class FavoriteProductAction
{
    public function execute(FavoriteProductRequest $request, Product $product)
    {
        $user = $request->user();

        try {
            $favorite = $user->favoriteProducts()
                ->whereKey($product->id)
                ->first();

            $shouldFavorite = $request->has('favorite')
                ? $request->validated()['favorite']
                : !$favorite;

            if ($shouldFavorite && !$favorite) {
                FavoriteProducts::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ]);
            }

            if (!$shouldFavorite && $favorite) {
                $favorite->delete();
            }
        } catch (\Throwable $th) {
            Log::alert([
                'error' => 'Problem with favoriting product',
                'message' => $th->getMessage(),
            ]);

            return response([
                'message' => 'Não foi possível remover o item dos seus favoritos',
            ], 400);
        }

        return response([
            'message' => $shouldFavorite ? 'Produto favoritado' : 'Produto desfavoritado',
        ]);
    }
}
