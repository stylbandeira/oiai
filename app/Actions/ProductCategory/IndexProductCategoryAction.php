<?php

namespace App\Actions\ProductCategory;

use App\Http\Resources\CategoryResource;
use App\Models\ProductCategory;

class IndexProductCategoryAction
{
    public function execute()
    {
        return CategoryResource::collection(ProductCategory::all());
    }
}
