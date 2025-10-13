<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unity;
use App\Rules\ExistsOr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240'
        ]);

        if ($validator->fails()) {
            return response([
                'message' => 'Arquivo inválido',
                'errors' => $validator->errors()
            ]);
        }

        try {
            $file = $request->file('file');
            $filePath = $file->store('temp-csv');

            $result = $this->processCSV($filePath);
            Storage::delete($filePath);
            $products = $result['data'];

            $validator = Validator::make($products, [
                '*.name' => 'required|string',
                '*.quantity' => 'required|integer',
                '*.unity' => ['required', new ExistsOr('unities', ['id', 'name'])],
                '*.category' => ['required', new ExistsOr('product_category', ['id', 'name'])],
                '*.img' => 'image',
                '*.sku' => 'required|string|unique:products,sku',
            ]);

            if ($validator->fails()) {
                return response([
                    'message' => 'O arquivo não pôde ser importado pois possui dados inválidos.',
                    'errors' => $validator->errors()
                ], 400);
            }

            $product_collection = collect($products);
            $category_names = $product_collection->pluck('category')
                ->filter(fn($c) => !is_numeric($c))
                ->map(fn($c) => mb_strtolower($c))
                ->unique()
                ->values();
            $categories = ProductCategory::whereIn('name', $category_names)
                ->pluck('id', 'name');

            $unity_names = $product_collection->pluck('unity')
                ->filter(fn($u) => !is_numeric($u))
                ->map(fn($u) => mb_strtolower($u))
                ->unique()
                ->values();
            $unities = Unity::whereIn('name', $unity_names)
                ->pluck('id', 'name');

            $products = $product_collection->map(function ($product) use ($categories, $unities) {
                if (!is_numeric($product['category'])) {
                    $product['category'] = $categories[$product['category']] ?? null;
                }

                if (!is_numeric($product['unity'])) {
                    $product['unity'] = $unities[$product['unity']] ?? null;
                }

                $product['average_price'] = $product['average_price'] == '' ? 0 : $product['average_price'];

                $product['category_id'] = $product['category'];
                unset($product['category']);
                $product['unit_id'] = $product['unity'];
                unset($product['unity']);

                return $product;
            });

            Product::insert($products->toArray());

            return response([
                'message' => 'CSV processado com sucesso!',
                'data' => $result
            ]);
        } catch (\Throwable $th) {
            Log::alert(['Catch' => $th->getMessage()]);
            return response([
                'message' => 'Erro ao processar CSV: ' . $th->getMessage()
            ]);
        }
    }

    private function processCSV($filePath)
    {
        $fullPath = Storage::path($filePath);
        $handle = fopen($fullPath, 'r');

        $headers = fgetcsv($handle, 1000, ',');
        $headerCount = count($headers);

        $rows = [];
        $rowCount = 0;
        $errors = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $rowCount++;

            // VERIFICA se tem o mesmo número de colunas
            if (count($row) !== $headerCount) {
                $errors[] = "Linha $rowCount: Esperado $headerCount colunas, encontrado " . count($row);
                continue; // Pula esta linha
            }

            // Agora pode combinar com segurança
            $rowData = array_combine($headers, $row);
            $rows[] = $rowData;
        }

        fclose($handle);

        return [
            'total_rows' => $rowCount,
            'processed_rows' => count($rows),
            'errors' => $errors,
            'sample_data' => array_slice($rows, 0, 3),
            'data' => $rows
        ];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return response([
            'message' => 'Produto deletada com sucesso!'
        ]);
    }
}
