<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\CompanyProducts;
use App\Models\Product;

class StoreProductAction
{
    public function execute(ProductStoreRequest $request)
    {
        $user = $request->user();

        $validatedData = $request->validated();
        $validatedData['created_by'] = $user->id;

        $company = null;

        if ($user->isCompany() && $request->company_id) {
            $company = $user->activeCompanies()->find($request->company_id);

            if (!$company) {
                return response([
                    'message' => 'Usuário company não possui empresa ativa.',
                ], 400);
            }
        }

        if ($request->hasFile('img')) {
            $imgPath = $request->file('img')->store('products/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        if ($user->type === 'client') {
            $validatedData['validated'] = false;
        }

        $product = Product::create($validatedData);

        if ($user->isCompany() && $company) {
            CompanyProducts::create([
                'product_id' => $product->id,
                'company_id' => $company->id,
                'average_price' => $validatedData['average_price'] ?? null,
            ]);
        }

        $product->load(['category', 'unity', 'companies']);

        return response([
            'product' => $user->type === 'admin'
                ? new AdminProductResource($product)
                : new ClientProductResource($product),
        ]);
    }
}
