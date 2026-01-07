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
     * @param Int $id
     * @param Request $request
     * @return void
     */
    public function update(Int $id, Request $request)
    {
        $list = ItensList::with('listProducts')
            ->find($id);

        $validator = Validator::make($request->all(), [
            'completed_items' => 'required|array',
            'completed_items.*' => 'integer|exists:products,id'
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ]);
        }

        try {
            Log::alert($request->completed_items);
            $list->listProducts()->update([
                'completed' => false
            ]);

            if (!empty($request->completed_items)) {
                $listItems = ListProducts::where('list_id', $list->id)
                    ->whereIn('product_id', $request->completed_items);

                $listItems->update(['completed' => true]);
            }

            return response([
                'message' => 'Itens marcados como concluídos com sucesso',
                'completed_count' => count($request->completed_items)
            ]);
        } catch (\Throwable $th) {
            return response([
                'message' => 'Erro ao atualizar lista',
                'error' => $th->getMessage()
            ]);
        }
    }
}
