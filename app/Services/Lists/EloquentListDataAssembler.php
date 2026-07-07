<?php

namespace App\Services\Lists;

use App\Contracts\ListDataAssembler;
use App\Http\Resources\ClientProductResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ListProductResource;
use App\Models\ItensList;
use App\Repositories\ListRepository;

class EloquentListDataAssembler implements ListDataAssembler
{
    public function __construct(private ListRepository $listRepository) {}

    public function assemble(ItensList $list): array
    {
        $this->listRepository->loadDefaultRelations($list);

        $data = [
            'id' => $list->id,
            'optimized' => (bool) $list->optimized,
            'user_id' => $list->user_id,
            'name' => $list->name,
            'favorite' => (bool) $list->favorite,
            'status' => $list->status,
            'total' => (float) $list->total,
            'created_at' => $list->created_at,
            'companies' => [],
            'products' => ListProductResource::collection($list->listProducts)->resolve(),
            'productsQuantity' => $list->listProducts->count(),
        ];

        if (! $list->optimized) {
            return $data;
        }

        foreach ($list->listProducts as $listProduct) {
            $companyProduct = $listProduct->companyProduct;

            if (! $companyProduct?->company) {
                continue;
            }

            $company = $companyProduct->company;
            $data['companies'][$company->id] ??= [
                'company' => (new CompanyResource($company))->resolve(),
                'products' => [],
            ];
            $data['companies'][$company->id]['products'][] = [
                'product' => (new ClientProductResource($listProduct->product))->resolve(),
                'average_price' => (float) $companyProduct->average_price,
            ];
        }

        return $data;
    }
}
