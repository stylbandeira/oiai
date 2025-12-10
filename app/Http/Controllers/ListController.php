<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClientListResource;
use App\Models\ItensList;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        //Cria lista
        $list = ItensList::create([
            'user_id' => $user->id,
            'name' => $request->listName,
            'favorite' => false,
            'total' => 0,
        ]);

        $productsWithQuantities = [];
        $productIds = array_column(array_column($request->products, 'product'), 'id');

        foreach ($request->products as $product) {
            // Log::alert(array_keys());
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
    public function show($id)
    {
        $list = ItensList::with(['listProducts' => function ($query) {
            $query->with(['product', 'companyProduct' => function ($query) {
                $query->with('company');
            }]);
        }])
            ->find($id);

        return response([
            'list' => new ClientListResource($list),
            'optimized' => $list->optimized
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
        $list = ItensList::find($id);

        if ($list->optimized) {
            $this->optimizeList($list);
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

    private function optimizeList(ItensList $list)
    {
        // $list->optimized = true;
        //empresas mais próximas (distância a definir TODO)
        //pega os produtos
        //verifica onde é mais barato
        //atribui esse
        $list->save();
    }
}
