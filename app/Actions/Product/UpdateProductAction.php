<?php

namespace App\Actions\Product;

use App\Http\Requests\Product\ProductUpdateRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class UpdateProductAction
{
    public function execute(ProductUpdateRequest $request, Product $product)
    {
        if ($request->user()->isClient() && $product->validated) {
            return response([
                'message' => 'Você não tem permissão para alterar esse produto',
            ], 403);
        }

        $validatedData = $request->validated();

        if ($request->hasFile('img')) {
            if ($product->img && Storage::disk('public')->exists($product->img)) {
                Storage::disk('public')->delete($product->img);
            }

            $imgPath = $request->file('img')->store('companies/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $product->update($validatedData);
        $product->refresh()->load(['category', 'unity', 'companies']);

        return response([
            'product' => $request->user()->type === 'admin'
                ? new AdminProductResource($product)
                : new ClientProductResource($product),
        ]);
    }
}
