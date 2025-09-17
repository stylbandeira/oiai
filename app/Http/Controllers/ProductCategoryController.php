<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        return response([
            'categories' => $categories
        ]);
    }
}
