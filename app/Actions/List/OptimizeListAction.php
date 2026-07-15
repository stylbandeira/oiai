<?php

namespace App\Actions\List;

use App\Http\Resources\ClientProductResource;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Repositories\CompanyProductsRepository;
use App\Repositories\ListProductsRepository;
use App\Repositories\ListRepository;

class OptimizeListAction
{
    public function __construct(
        private CompanyProductsRepository $companyProductsRepository,
        private ListProductsRepository $listProductsRepository,
        private ListRepository $listRepository,
    ) {}
    public function execute(string $list_id)
    {
        $list = ItensList::with('products')->find($list_id);

        $optimizedList = [];

        $companyProducts = $this->companyProductsRepository->getByProductIdsWithPivots($list->products->pluck('id')->toArray());

        $cheapest = $companyProducts->groupBy('product_id')
            ->map(function ($items) {
                return $items
                    ->filter(fn($item) => $item->average_price !== null && (float) $item->average_price > 0)
                    ->sortBy(fn($item) => (float) $item->average_price)
                    ->first();
            })
            ->filter();

        foreach ($cheapest as $cheap) {
            $product = (new ClientProductResource($cheap->product))->resolve();
            $product['average_price'] = (float) $cheap->average_price;

            $optimizedList[$cheap->company->name][] = $product;

            $this->listProductsRepository->updateProductsOnList([$cheap->product_id], $list->id, [
                'company_product_id' => $cheap->id,
            ]);
        }

        $this->listRepository->update($list->id, ['optimized' => true]);

        return $optimizedList;
    }
}
