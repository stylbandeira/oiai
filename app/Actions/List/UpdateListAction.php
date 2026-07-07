<?php

namespace App\Actions\List;

use App\Http\Requests\List\ListUpdateRequest;
use App\Models\ItensList;
use App\Repositories\ListProductsRepository;
use App\Repositories\ListRepository;

class UpdateListAction
{
    public function __construct(
        private ListRepository $listRepository,
        private ListProductsRepository $listProductsRepository,
    ) {}

    public function execute(ListUpdateRequest $request, ItensList $list)
    {
        $list->load('listProducts');
        $this->listRepository->update($list->id, $request->safe()->except('items'));

        $completedProductIds = $this->listRepository->getCompletedProductIds($list);


        if ($request->has('items')) {

            $this->listRepository->deleteIncompleteItems($list);

            foreach ($request->validated()['items'] as $item) {
                if (in_array($item['product_id'], $completedProductIds)) {
                    continue;
                }

                $this->listProductsRepository->createProductOnList(
                    $item['product_id'],
                    $list->id,
                    ['quantity' => $item['quantity']]
                );
            }
        }

        return response([
            'list' => $list,
        ]);
    }
}
