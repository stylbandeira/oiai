<?php

namespace App\Actions\List;

use App\Http\Resources\ClientProductResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ListProductResource;
use App\Models\ItensList;
use App\Repositories\ListRepository;

class ShowListAction
{
    public function __construct(
        private ListRepository $listRepository,
    ) {}
    public function execute(ItensList $list)
    {
        $this->listRepository->loadDefaultRelations($list);

        $responseList = [
            'id' => $list->id,
            'optimized' => $list->optimized,
            'user_id' => $list->user_id,
            'name' => $list->name,
            'favorite' => boolval($list->favorite),
            'status' => $list->status,
            'total' => floatval($list->total),
            'created_at' => $list->created_at,
            'companies' => [],
            'products' => ListProductResource::collection($list->listProducts),
            'productsQuantity' => $list->products()->count(),
        ];

        if ($list->optimized) {
            foreach ($list->listProducts as $product) {
                if (!isset($responseList['companies'][$product->companyProduct->company->id]) || !isset($responseList['companies'])) {
                    $responseList['companies'][$product->companyProduct->company->id] = (object) [
                        'company' => new CompanyResource($product->companyProduct->company),
                        'products' => [],
                    ];
                }

                $responseList['companies'][$product->companyProduct->company->id]->products[] = (object) [
                    'product' => new ClientProductResource($product->product),
                    'average_price' => $product->companyProduct->average_price,
                ];
            }
        }

        return response([
            'list' => $responseList,
            'optimized' => (bool) $list->optimized,
        ]);
    }
}
