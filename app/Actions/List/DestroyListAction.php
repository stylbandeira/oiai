<?php

namespace App\Actions\List;

use App\Models\ItensList;

class DestroyListAction
{
    public function execute(ItensList $list)
    {
        $list->delete();

        return response([
            'message' => 'Lista deletada com sucesso!',
        ]);
    }
}
