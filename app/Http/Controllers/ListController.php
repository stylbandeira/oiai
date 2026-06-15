<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClientListResource;
use App\Http\Resources\ClientProductResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ListProductResource;
use App\Http\Requests\List\ListStoreRequest;
use App\Http\Requests\List\ListUpdateRequest;
use App\Models\CompanyProducts;
use App\Models\ItensList;
use App\Models\ListProducts;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(ItensList::class, 'list');
    }

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
            ->with(['products', 'listProducts.product.unity', 'listProducts.product.category'])
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage);

        return ClientListResource::collection($itensList);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ListStoreRequest $request)
    {
        $user = Auth::user();

        if ($user->type !== 'client') {
            return response([
                'message' => 'Apenas clientes podem criar listas.'
            ], 403);
        }

        $list = DB::transaction(function () use ($request, $user) {
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

            return $list->load(['products', 'listProducts.product.unity', 'listProducts.product.category']);
        });

        return response([
            'message' => 'Lista criada com sucesso!',
            'list' => new ClientListResource($list)
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param ItensList $list
     * @return void
     */
    public function show(ItensList $list)
    {
        $list->load([
            'listProducts.product.unity',
            'listProducts.product.category',
            'listProducts.companyProduct.company'
        ]);

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
                if (!isset($l['companies'][$product->companyProduct->company->id]) || !isset($l['companies'])) {
                    $l['companies'][$product->companyProduct->company->id] = (object) [
                        'company' => new CompanyResource($product->companyProduct->company),
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
            'optimized' => (bool) $list->optimized
        ]);
    }

    public function optimize(Request $request, ItensList $list)
    {
        $list->load('products');

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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  ItensList  $list
     * @return \Illuminate\Http\Response
     */
    public function update(ListUpdateRequest $request, ItensList $list)
    {
        $list->load('listProducts');
        $list->update($request->safe()->except('items'));

        $completedProductIds = $list->listProducts()
            ->where('completed', true)
            ->pluck('product_id')
            ->toArray();

        $list->listProducts()->where('completed', 0)->delete();

        if ($request->has('items')) {
            foreach ($request->validated()['items'] as $item) {
                if (in_array($item['product_id'], $completedProductIds)) {
                    continue;
                }

                $list->listProducts()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        }

        return response([
            'list' => $list
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  ItensList  $list
     * @return \Illuminate\Http\Response
     */
    public function destroy(ItensList $list)
    {
        $list->delete();

        return response([
            'message' => 'Lista deletada com sucesso!'
        ]);
    }
}
