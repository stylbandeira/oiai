<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClientListResource;
use App\Http\Resources\ClientProductResource;
use App\Http\Resources\ListProductResource;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\at;

class ListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->per_page ?? 15;

        $itensList = ItensList::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->with('products')
            ->paginate($perPage);

        return response([
            'itensLists' => ClientListResource::collection($itensList)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response([
                'message' => 'Erro ao tentar criar lista',
                'errors' => $validator->errors()
            ], 403);
        }

        //Cria lista
        $list = ItensList::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'favorite' => false,
            'total' => 0,
        ]);

        $productsWithQuantities = [];
        $productIds = array_column(array_column($request->products, 'product'), 'id');

        foreach ($request->products as $product) {
            $productsWithQuantities[$product['product']['id']] = ['quantity' => $product['quantity']];
        }

        $list->products()->attach($productsWithQuantities);
        Product::whereIn('id', $productIds)->increment('listAdded');

        return response([
            'message' => 'Lista criada com sucesso!',
            'list' => $list->with('products')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, int $id)
    {
        $list = ItensList::with([
            'listProducts.product.unity',
            'listProducts.product.category',
            'listProducts.companyProduct.company'
        ])->find($id);

        if (!$list) {
            return response([
                'error' => 'List not found',
                'message' => 'Lista não encontrada'
            ], 404);
        }

        $l = [
            'id' => $list->id,
            'optimized' => $list->optimized,
            'user_id' => $list->user_id,
            'name' => $list->name,
            'favorite' => boolval($list->favorite),
            'status' => $list->status,
            'total' => floatval($list->total),
            'created_at' => $list->created_at,
            'companies' => [],
            'products' => ListProductResource::collection($list->listProducts),
            'productsQuantity' => $list->products()->count(),
        ];

        if ($list->optimized) {
            $products_list = $list->listProducts;

            foreach ($products_list as $product) {
                if (!isset($l['companies'][$product->companyProduct->company->id])) {
                    $l['companies'][$product->companyProduct->company->id] = (object) [
                        'company' => $product->companyProduct->company,
                        'products' => [] // Inicializa array vazio de produtos
                    ];
                }

                $l['companies'][$product->companyProduct->company->id]->products[] = (object) [
                    'product' => new ClientProductResource($product->product),
                    'average_price' => $product->companyProduct->average_price,
                ];
            }
        }

        return response([
            'list' => $l,
            'optimized' => $list->optimized
        ]);
    }

    public function optimize(ItensList $list)
    {
        $r = [];

        $produtos = CompanyProducts::whereIn('product_id', $list->products->pluck('id'))
            ->with(['product', 'company'])
            ->get();


        $cheapest = $produtos->groupBy('product_id')
            ->map(function ($items) {
                $minPrice = $items->min('average_price');
                return $items->filter(function ($item) use ($minPrice) {
                    return $item->average_price == $minPrice;
                })->values();
            })
            ->flatten(1);

        foreach ($cheapest as $cheap) {
            $r[$cheap->company->name][] = new ClientProductResource($cheap->product);

            ListProducts::where('list_id', $list->id)
                ->where('product_id', $cheap->product_id)
                ->update([
                    'company_product_id' => $cheap->id
                ]);
        }

        $list->optimized = true;
        $list->save();

        return response([
            'list' => $r
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $list = ItensList::with('listProducts')->find($id);

        $list->update([
            'name' => $request->name
        ]);

        $list->listProducts()->where('completed', 0)->delete();

        foreach ($request->items as $item) {
            $list->listProducts()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return response([
            'list' => $list
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
        $list = ItensList::find($id);

        $list->delete();

        return response([
            'message' => 'Lista deletada com sucesso!'
        ]);
    }
}
