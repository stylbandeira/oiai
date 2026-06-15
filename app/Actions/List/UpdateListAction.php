<?php

namespace App\Actions\List;

use App\Http\Requests\List\ListUpdateRequest;
use App\Models\ItensList;

class UpdateListAction
{
    public function execute(ListUpdateRequest $request, ItensList $list)
    {
        $list->load('listProducts');
        $list->update($request->safe()->except('items'));

        $completedProductIds = $list->listProducts()
            ->where('completed', true)
            ->pluck('product_id')
            ->toArray();

        $list->listProducts()->where('completed', 0)->delete();

        if ($request->has('items')) {
            foreach ($request->validated()['items'] as $item) {
                if (in_array($item['product_id'], $completedProductIds)) {
                    continue;
                }

                $list->listProducts()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        }

        return response([
            'list' => $list,
        ]);
    }
}
