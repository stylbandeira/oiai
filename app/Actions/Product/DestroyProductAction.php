<?php

namespace App\Actions\Product;

use App\Models\Product;

class DestroyProductAction
{
    public function execute(Product $product)
    {
        $product->delete();

        return response([
            'message' => 'Produto deletada com sucesso!',
        ]);
    }
}
