<?php

namespace App\Http\Controllers;

use App\Http\Requests\FavoriteProducts\FavoriteProductRequest;
use App\Models\FavoriteProducts;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FavoriteProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
    }

    /**
     * Favorite or unfavorite a product from an user
     *
     * @param Request $request
     * @param Product $product
     * @return void
     */
    public function favorite(FavoriteProductRequest $request, Product $product)
    {
        $user = $request->user();

        try {
            $favorite = FavoriteProducts::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            $shouldFavorite = $request->has('favorite')
                ? $request->validated()['favorite']
                : !$favorite;

            if ($shouldFavorite && !$favorite) {
                FavoriteProducts::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id
                ]);
            }

            if (!$shouldFavorite && $favorite) {
                $favorite->delete();
            }
        } catch (\Throwable $th) {
            Log::alert([
                'error' => 'Problem with favoriting product',
                'message' => $th->getMessage()
            ]);

            return response([
                'message' => 'Não foi possível remover o item dos seus favoritos'
            ], 400);
        }

        return response([
            'message' => $shouldFavorite ? 'Produto favoritado' : 'Produto desfavoritado'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
