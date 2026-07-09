<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessInvoiceJob;
use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Unity;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\NFCeScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProcessInvoiceJobTest extends TestCase
{
    use RefreshDatabase;

    private const VALID_ACCESS_KEY = '26260712345678000195650010000012341000012345';

    public function test_invoice_price_is_recorded_without_overwriting_existing_averages(): void
    {
        $user = User::factory()->client()->create();
        Unity::factory()->create(['abbreviation' => 'un']);
        $company = Company::factory()->create([
            'cnpj' => '45543915100640',
            'ie' => '123456789',
        ]);
        $product = Product::factory()->create([
            'ean' => '7804622380587',
            'average_price' => 27.49,
        ]);
        $companyProduct = CompanyProducts::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'average_price' => 27.99,
        ]);
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'access_key' => 'invoice-average-price-test',
            'receipt_data' => now()->toDateString(),
            'invoice_data' => json_encode([
                'emitente' => [
                    'cnpj' => $company->cnpj,
                    'ie' => $company->ie,
                    'razao_social' => $company->name,
                    'endereco' => 'Rua Teste',
                    'numero' => '100',
                    'bairro' => 'Centro',
                    'municipio' => 'Petrolina',
                    'uf' => 'PE',
                    'cep' => '56300000',
                ],
                'produtos' => [[
                    'ean' => $product->ean,
                    'codigo' => $product->sku,
                    'descricao' => $product->name,
                    'unidade' => 'UN',
                    'valor_unitario' => 25.99,
                ]],
                'dados_nota' => [
                    'data_emissao' => now()->format('d/m/Y H:i:sP'),
                ],
            ]),
            'pending' => true,
        ]);

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('createProductInsertionEvent')->once();

        (new ProcessInvoiceJob($notificationService))->processInvoice($invoice);

        $this->assertEquals(27.49, $product->fresh()->average_price);
        $this->assertEquals(27.99, $companyProduct->fresh()->average_price);
        $this->assertDatabaseHas('user_added_products', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'product_id' => $product->id,
            'company_product_id' => $companyProduct->id,
            'price' => 25.99,
        ]);
    }

    public function test_pending_invoice_with_only_access_key_is_validated_and_processed_by_job(): void
    {
        $user = User::factory()->client()->create();
        Unity::factory()->create(['abbreviation' => 'un']);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'access_key' => self::VALID_ACCESS_KEY,
            'receipt_data' => now()->toDateString(),
            'invoice_data' => null,
            'pending' => true,
        ]);

        $this->mock(NFCeScraperService::class, function ($mock) {
            $mock->shouldReceive('scrapeFromQRCode')
                ->once()
                ->with(self::VALID_ACCESS_KEY)
                ->andReturn([
                    'status' => 'success',
                    'data' => [
                        'emitente' => [
                            'cnpj' => '45543915100640',
                            'ie' => '123456789',
                            'razao_social' => 'Mercado Teste',
                            'endereco' => 'Rua Teste',
                            'numero' => '100',
                            'bairro' => 'Centro',
                            'municipio' => 'Petrolina',
                            'uf' => 'PE',
                            'cep' => '56300000',
                        ],
                        'produtos' => [[
                            'ean' => '7804622380587',
                            'codigo' => 'SKU-123',
                            'descricao' => 'Produto Teste',
                            'unidade' => 'UN',
                            'valor_unitario' => 25.99,
                        ]],
                        'dados_nota' => [
                            'data_emissao' => now()->format('d/m/Y H:i:sP'),
                        ],
                    ],
                ]);
        });

        $notificationService = Mockery::mock(NotificationService::class);
        $notificationService->shouldReceive('createProductInsertionEvent')->once();

        (new ProcessInvoiceJob($notificationService))->processInvoice($invoice);

        $this->assertFalse((bool) $invoice->fresh()->pending);
        $this->assertNotNull($invoice->fresh()->invoice_data);
        $this->assertDatabaseHas('products', [
            'ean' => '7804622380587',
            'name' => 'Produto Teste',
        ]);
        $this->assertDatabaseHas('user_added_products', [
            'user_id' => $user->id,
            'price' => 25.99,
        ]);
    }
}
