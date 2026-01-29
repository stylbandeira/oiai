<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\Product;
use App\Models\Unity;
use App\Models\User;
use App\Models\UserAddedProducts;
use App\Repositories\EventRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $company;
    protected $products_data;
    protected $user;
    protected $not_inserted_products = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Company $company, array $products_data, User $user)
    {
        $this->company = $company;
        $this->products_data = $products_data;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $unities = Unity::all()->keyBy('abbreviation')->toArray();
        $user_inserted_products = [];

        foreach ($this->products_data as $productData) {

            //VERIFICA SE É PRODUTO SEM EAN
            $insert = !is_numeric($productData['ean']) ?
                ['sku' => $productData['codigo']] :
                ['ean' => $productData['ean']];

            $insertedProduct = Product::updateOrCreate(
                $insert,
                [
                    'sku' => $productData['codigo'],
                    'description' => $productData['descricao'],
                    'name' => $productData['descricao'],
                    'unit_id' => $unities[strtolower($productData['unidade'])]['id'] ?? 1,
                    'quantity' => 1,
                    'average_price' => $productData['valor_unitario']
                ],
            );

            $user_inserted_products[] = [
                'user_id' => $this->user->id,
                'price' => $productData['valor_unitario'] ?? 0,
                'company_id' => $this->company->id,
                'product_id' => $insertedProduct->id
            ];

            if ($insertedProduct->wasChanged  || $insertedProduct->wasRecentlyCreated) {
                try {

                    if (!$user_inserted_products) {
                        UserAddedProducts::insert($user_inserted_products);
                    }

                    CompanyProducts::updateOrCreate(
                        [
                            'product_id' => $insertedProduct->id,
                            'company_id' => $this->company->id,
                        ],
                        [
                            'average_price' => $productData['valor_unitario']
                        ]
                    );
                } catch (\Throwable $th) {
                    $this->not_inserted_products[] = [
                        'product_error',
                        'product_data' => $productData,
                        'company' => $this->company
                    ];
                }
            }
        }

        try {
            $eventRepo = app(EventRepository::class);
            $eventRepo->createProductInsertionEvent($this->user, count($this->products_data), $this->company);
        } catch (\Throwable $th) {
            Log::alert('Problema com criação de evento de inserção de produtos');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falha ao processar pedido: ' . $exception->getMessage(), $this->not_inserted_products);
    }
}
