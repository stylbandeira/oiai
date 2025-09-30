<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });
        }

        $perPage = $request->per_page ?? 10;

        $products = $query->with(['category', 'unity'])
            ->paginate($perPage);

        if ($request->user()->type === 'admin') {
            return AdminProductResource::collection($products);
        }

        return ClientProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'quantity' => 'required|integer',
            'unit_id' => 'required|exists:unities,id',
            'category_id' => 'required|exists:product_category,id',
            'img' => 'image'
        ]);

        if ($validate->fails()) {
            return response([
                'errors' => $validate->errors()
            ], 400);
        }

        $validatedData = $request->all();

        if ($request->hasFile('img')) {
            $imgPath = $request->file('img')->store('products/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $product = Product::create($validatedData);

        return response([
            'product' => $product
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();


        $product = Product::with(['category', 'unity'])->find($id);

        return new AdminProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'sku' => 'string|unique:products,sku,' . $product->id,
            'img' => 'image',
            'unit_id' => 'exists:unities,id',
            'category_id' => 'exists:product_category,id',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 400);
        }

        $validatedData = $request->all();

        if ($request->hasFile('img')) {

            if ($product->img && Storage::disk('public')->exists($product->img)) {
                Storage::disk('public')->delete($product->img);
            }

            $imgPath = $request->file('img')->store('companies/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $product->update($validatedData);

        return response([
            'product' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
