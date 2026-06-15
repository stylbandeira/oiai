<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductIndexRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Repositories\ProductRepository;

class IndexProductAction
{
    public function __construct(private ProductRepository $productRepo)
    {
    }

    public function execute(ProductIndexRequest $request)
    {
        $perPage = $request->per_page ?? 15;
        $products = $this->productRepo
            ->list($request->user(), $request, ['category', 'unity', 'companies'])
            ->paginate($perPage);

        if ($request->user()->type === 'admin') {
            return AdminProductResource::collection($products);
        }

        return ClientProductResource::collection($products);
    }
}
