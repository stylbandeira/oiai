<?php

namespace App\Actions\List;

use App\Http\Resources\ClientProductResource;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\ListProducts;
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
    public function execute(ItensList $list)
    {
        $list->load('products');

        $optimizedList = [];

        $companyProducts = $this->companyProductsRepository->getByProductIdsWithPivots($list->products->pluck('id')->toArray());

        $cheapest = $companyProducts->groupBy('product_id')
            ->map(function ($items) {
                $minPrice = $items->min('average_price');
                return $items->filter(function ($item) use ($minPrice) {
                    return $item->average_price == $minPrice;
                })->values();
            })
            ->flatten(1);

        foreach ($cheapest as $cheap) {
            $optimizedList[$cheap->company->name][] = new ClientProductResource($cheap->product);

            $this->listProductsRepository->updateProductOnList($cheap->product_id, $list->id, [
                'company_product_id' => $cheap->id,
            ]);
        }

        $this->listRepository->update($list->id, ['optimized' => true]);

        return response([
            'list' => $optimizedList,
        ]);
    }
}
