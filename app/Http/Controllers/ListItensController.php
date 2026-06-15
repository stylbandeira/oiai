<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListItens\ListItensUpdateRequest;
use App\Models\ItensList;
use App\Models\ListProducts;

class ListItensController extends Controller
{
    /**
     * Update list itens
     *
     * @param ItensList $list
     * @param ListItensUpdateRequest $request
     * @return void
     */
    public function update(ListItensUpdateRequest $request, ItensList $list)
    {
        $list->load('listProducts');

        try {
            $list->listProducts()->update([
                'completed' => false
            ]);

            $completedItems = $request->validated()['completed_items'];

            if (!empty($completedItems)) {
                $listItems = ListProducts::where('list_id', $list->id)
                    ->whereIn('product_id', $completedItems);

                $listItems->update(['completed' => true]);
            }

            return response([
                'message' => 'Itens marcados como concluídos com sucesso',
                'completed_count' => count($completedItems)
            ]);
        } catch (\Throwable $th) {
            return response([
                'message' => 'Erro ao atualizar lista',
                'error' => $th->getMessage()
            ]);
        }
    }
}
