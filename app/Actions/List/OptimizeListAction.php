<?php

namespace App\Actions\List;

use App\Http\Resources\ClientProductResource;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\ListProducts;

class OptimizeListAction
{
    public function execute(ItensList $list)
    {
        $list->load('products');

        $response = [];

        $companyProducts = CompanyProducts::whereIn('product_id', $list->products->pluck('id'))
            ->with(['product', 'company'])
            ->get();

        $cheapest = $companyProducts->groupBy('product_id')
            ->map(function ($items) {
                $minPrice = $items->min('average_price');
                return $items->filter(function ($item) use ($minPrice) {
                    return $item->average_price == $minPrice;
                })->values();
            })
            ->flatten(1);

        foreach ($cheapest as $cheap) {
            $response[$cheap->company->name][] = new ClientProductResource($cheap->product);

            ListProducts::where('list_id', $list->id)
                ->where('product_id', $cheap->product_id)
                ->update([
                    'company_product_id' => $cheap->id,
                ]);
        }

        $list->optimized = true;
        $list->save();

        return response([
            'list' => $response,
        ]);
    }
}
