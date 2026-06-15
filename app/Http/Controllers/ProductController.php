<?php

namespace App\Http\Controllers;

use App\Actions\Product\BulkValidateProductAction;
use App\Actions\Product\DestroyProductAction;
use App\Actions\Product\ExportProductAction;
use App\Actions\Product\ImportProductAction;
use App\Actions\Product\IndexProductAction;
use App\Actions\Product\ShowProductAction;
use App\Actions\Product\StoreProductAction;
use App\Actions\Product\UpdateProductAction;
use App\Http\Requests\Product\ProductBulkValidateRequest;
use App\Http\Requests\Product\ProductExportRequest;
use App\Http\Requests\Product\ProductImportRequest;
use App\Http\Requests\Product\ProductIndexRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Product;
use App\Services\ExportService;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ProductIndexRequest $request, IndexProductAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductStoreRequest $request, StoreProductAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product, ShowProductAction $action)
    {
        return $action->execute($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductUpdateRequest $request, Product $product, UpdateProductAction $action)
    {
        return $action->execute($request, $product);
    }

    public function import(ProductImportRequest $request, ImportProductAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Exports an CSV file of products
     *
     * @param ProductExportRequest $request
     * @param ExportService $exportService
     * @return void
     */
    public function export(ProductExportRequest $request, ExportService $exportService, ExportProductAction $action)
    {
        return $action->execute($request, $exportService);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product, DestroyProductAction $action)
    {
        return $action->execute($product);
    }

    public function bulkValidate(ProductBulkValidateRequest $request, BulkValidateProductAction $action)
    {
        return $action->execute($request);
    }
}
