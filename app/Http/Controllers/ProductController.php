<?php

namespace App\Http\Controllers;

use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ClientProductResource;
use App\Models\CompanyProducts;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unity;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Rules\ExistsOr;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected $productRepo;
    protected $userRepo;

    public function __construct(ProductRepository $productRepo, UserRepository $userRepo)
    {
        $this->productRepo = $productRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'sometimes|string',
            'validated' => 'sometimes|in:pendentes,validados',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        $perPage = $request->per_page ?? 15;
        $products = $this->productRepo
            ->list($request->user(), $request, ['category', 'unity', 'companies'])
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
        $user = $request->user();

        $validate = Validator::make($request->all(), [
            'name' => 'required|string',
            'sku' => 'required|string|unique:products,sku',
            'quantity' => 'required|integer',
            'unit_id' => 'required|exists:unities,id',
            'category_id' => 'required|exists:product_category,id',
            'average_price' => 'nullable|numeric',
            'ean' => 'nullable|string|unique:products,ean',
            'description' => 'nullable|string',
            'validated' => 'sometimes|boolean',
            'img' => 'image',
            'company_id' => 'sometimes|exists:company,id'
        ]);

        if ($validate->fails()) {
            return response([
                'errors' => $validate->errors()
            ], 422);
        }

        $validatedData = $validate->validated();
        $validatedData['created_by'] = $user->id;

        $company = null;

        if ($user->isCompany() && $request->company_id) {
            $company = $user->activeCompanies()->find($request->company_id);

            if (!$company) {
                return response([
                    'message' => 'Usuário company não possui empresa ativa.'
                ], 400);
            }
        }

        if ($request->hasFile('img')) {
            $imgPath = $request->file('img')->store('products/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        if ($user->type === 'client') {
            $validatedData['validated'] = false;
        }

        $product = Product::create($validatedData);

        if ($user->isCompany() && $company) {
            CompanyProducts::create([
                'product_id' => $product->id,
                'company_id' => $company->id,
                'average_price' => $validatedData['average_price'] ?? null,
            ]);
        }

        $product->load(['category', 'unity', 'companies']);

        return response([
            'product' => $user->type === 'admin'
                ? new AdminProductResource($product)
                : new ClientProductResource($product)
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        $user = Auth::user();

        $product->load(['category', 'unity', 'companies']);

        if ($user->type === 'client') {
            return new ClientProductResource($product);
        }

        return new AdminProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Product  $product
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
            'quantity' => 'integer',
            'average_price' => 'nullable|numeric',
            'ean' => 'nullable|string|unique:products,ean,' . $product->id,
            'description' => 'nullable|string',
            'validated' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->user()->isClient() && $product->validated) {
            return response([
                'message' => 'Você não tem permissão para alterar esse produto',
            ], 403);
        }

        $validatedData = $validator->validated();

        if ($request->hasFile('img')) {

            if ($product->img && Storage::disk('public')->exists($product->img)) {
                Storage::disk('public')->delete($product->img);
            }

            $imgPath = $request->file('img')->store('companies/images', 'public');
            $validatedData['img'] = $imgPath;
        }

        $product->update($validatedData);
        $product->refresh()->load(['category', 'unity', 'companies']);

        return response([
            'product' => $request->user()->type === 'admin'
                ? new AdminProductResource($product)
                : new ClientProductResource($product)
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
            ], 422);
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
     * Exports an CSV file of products
     *
     * @param Request $request
     * @param ExportService $exportService
     * @return void
     */
    public function export(Request $request, ExportService $exportService)
    {
        $query = Product::with(['category', 'unity']);

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });
        }

        $products = $query->get();

        $columns = [
            'Name' => 'name',
            'Sku' => 'sku',
            'Category' => 'category.name',
            'Unity' => 'unity.name',
            'Quantity' => 'quantity',
            'Unity' => 'unity.name',
            'Quantity' => 'quantity',
            'Preço Médio (R$)' => function ($product) {
                return $product->average_price ? number_format($product->average_price, 2, ',', '.') : '0.00';
            },
        ];

        return $exportService->exportToCSV($products, $columns, 'produtos');
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

    public function bulkValidate(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'validated' => 'required|boolean'
        ]);

        $productsToScore = collect();

        if ($validated['validated']) {
            $productsToScore = Product::whereIn('id', $validated['product_ids'])
                ->where('validated', false)
                ->whereNull('validated_by')
                ->whereNotNull('created_by')
                ->get(['id', 'created_by']);
        }

        Product::whereIn('id', $validated['product_ids'])
            ->update([
                'validated' => $validated['validated'],
                'validated_by' => $request->user()->id
            ]);

        foreach ($productsToScore as $product) {
            $this->userRepo->addPoints($product->created_by, 3);
        }

        return response([
            'message' => 'Produtos atualizados com sucesso!',
            'count' => count($validated['product_ids'])
        ]);
    }
}
