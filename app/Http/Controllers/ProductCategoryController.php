<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    /**
     * Return all categories
     *
     * @return void
     */
    public function index()
    {
        $categories = ProductCategory::all();

        return CategoryResource::collection($categories);
    }
}
