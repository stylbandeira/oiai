<?php

namespace App\Jobs;

use App\Models\Address;
use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Unity;
use App\Models\User;
use App\Models\UserAddedProducts;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $unities;
    protected $not_inserted_products = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private NotificationService $notificationService)
    {
        $this->unities = Unity::all()->keyBy('abbreviation')->toArray();
    }

    public function handle()
    {
        $this->processPendingInvoices();
    }

    public function processPendingInvoices()
    {
        $invoices = Invoice::where('created_at', '>=', now()->subMonth())
            ->where('invoice_data', '!=', null)
            ->where('pending', 1)
            ->get();

        foreach ($invoices as $invoice) {
            $this->processInvoice($invoice);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function processInvoice(Invoice $invoice)
    {
        $user_inserted_products = [];
        $invoice_data = json_decode($invoice->invoice_data);

        $products_data = $invoice_data->produtos;
        $user = User::find($invoice->user_id);

        //FIRST OR CREATE DE COMPANY
        $company_data = $invoice_data->emitente;
        $company = Company::updateOrCreate(
            [
                'cnpj' => $company_data->cnpj,
                'ie' => $company_data->ie
            ],
            [
                'name' => $company_data->razao_social,
                'cnpj' => $company_data->cnpj,
                'raw_address' => $company_data->endereco
                    . ' - ' . ($company_data->numero ?? '')
                    . ', ' . $company_data->bairro
                    . ', ' . $company_data->municipio
                    . ', ' . $company_data->uf,
                'phone' => ($company_data->telefone ?? ''),
            ]
        );

        if ($company->wasChanged  || $company->wasRecentlyCreated) {
            $address = Address::firstOrCreate([
                'area' => $company_data->bairro,
                'city' => $company_data->municipio,
                'street' => $company_data->endereco,
                'number' => ($company_data->numero ?? ''),
            ], [
                'state' => $company_data->uf,
                'cep' => $company_data->cep ?? null,
            ]);

            $company->address_id = $address->id;
            $company->save();
        }

        foreach ($products_data as $productData) {
            //VERIFICA SE É PRODUTO SEM EAN
            $insert = !is_numeric($productData->ean) ?
                ['sku' => $productData->codigo] :
                ['ean' => $productData->ean];

            $insertedProduct = Product::updateOrCreate(
                $insert,
                [
                    'sku' => $productData->codigo,
                    'description' => $productData->descricao,
                    'name' => $productData->descricao,
                    'unit_id' => $this->unities[strtolower($productData->unidade)]['id'] ?? 1,
                    'quantity' => 1,
                    'created_by' => $user->id
                ],
            );

            if (true) {

                if (!$insertedProduct->wasRecentlyCreated) {
                    $insertedProduct->mentioned_quantity++;
                    $insertedProduct->save();
                }

                try {
                    $company_product = CompanyProducts::firstOrCreate(
                        [
                            'product_id' => $insertedProduct->id,
                            'company_id' => $company->id,
                        ],
                        [
                            'average_price' => null
                        ]
                    );

                    try {
                        $dataBruta = $invoice_data->dados_nota->data_emissao; // "24/03/2026 20:14:32-03:00"

                        $purchase_date = Carbon::createFromFormat('d/m/Y H:i:sP', $dataBruta);

                        Log::alert('SUCESSO: ' . $purchase_date);
                    } catch (\Exception $e) {
                        Log::alert('ERRO: ' . $e->getMessage());
                    }

                    $user_inserted_products[] = [
                        'user_id' => $user->id,
                        'price' => $productData->valor_unitario ?? 0,
                        'company_id' => $company->id,
                        'product_id' => $insertedProduct->id,
                        'created_at' => Carbon::now(),
                        'company_product_id' => $company_product->id,
                        'purchase_date' => $purchase_date
                    ];
                } catch (\Throwable $th) {
                    $this->not_inserted_products[] = [
                        'product_error',
                        'product_data' => $productData,
                        'company' => $company
                    ];
                }
            }
        }

        try {
            UserAddedProducts::insert($user_inserted_products);
        } catch (\Throwable $th) {
            Log::alert([
                'error' => 'UserAddedProducts not working',
                'message' => $th->getMessage()
            ]);
        }

        $userRepo = new UserRepository($user);
        $userRepo->addPoints($user->id, count($products_data));

        try {
            $this->notificationService->createProductInsertionEvent($user, count($products_data), $company);
        } catch (\Throwable $th) {
            Log::alert('Problema com criação de evento de inserção de produtos');
        }

        $invoice->pending = false;
        $invoice->save();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falha ao processar pedido: ' . $exception->getMessage(), $this->not_inserted_products);
    }
}
