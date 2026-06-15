<?php

namespace App\Http\Controllers;

use App\Models\ItensList;
use App\Models\ListProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ListItensController extends Controller
{
    /**
     * Update list itens
     *
     * @param ItensList $list
     * @param Request $request
     * @return void
     */
    public function update(Request $request, ItensList $list)
    {
        $list->load('listProducts');

        $validator = Validator::make($request->all(), [
            'completed_items' => 'required|array',
            'completed_items.*' => 'integer|exists:products,id'
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $list->listProducts()->update([
                'completed' => false
            ]);

            $completedItems = $validator->validated()['completed_items'];

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
