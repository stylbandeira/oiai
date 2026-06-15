<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductBulkValidateRequest;
use App\Models\Product;
use App\Repositories\UserRepository;

class BulkValidateProductAction
{
    public function __construct(private UserRepository $userRepo)
    {
    }

    public function execute(ProductBulkValidateRequest $request)
    {
        $validated = $request->validated();

        $productsToScore = collect();

        if ($validated['validated']) {
            $productsToScore = Product::whereIn('id', $validated['product_ids'])
                ->where('validated', false)
                ->whereNull('validated_by')
                ->whereNotNull('created_by')
                ->get(['id', 'created_by']);
        }

        Product::whereIn('id', $validated['product_ids'])
            ->update([
                'validated' => $validated['validated'],
                'validated_by' => $request->user()->id,
            ]);

        foreach ($productsToScore as $product) {
            $this->userRepo->addPoints($product->created_by, 3);
        }

        return response([
            'message' => 'Produtos atualizados com sucesso!',
            'count' => count($validated['product_ids']),
        ]);
    }
}
