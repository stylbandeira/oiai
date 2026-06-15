<?php

namespace App\Actions\Product;

use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class ShowProductAction
{
    public function execute(Product $product)
    {
        $user = Auth::user();

        $product->load(['category', 'unity', 'companies']);

        if ($user->type === 'client') {
            return new ClientProductResource($product);
        }

        return new AdminProductResource($product);
    }
}
