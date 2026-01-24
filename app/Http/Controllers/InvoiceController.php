<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Company;
use App\Models\CompanyProducts;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Unity;
use App\Repositories\UserRepository;
use App\Services\NFCeScraperService;
use App\Services\NFCeXMLParserService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected $scraper;
    protected $xmlParser;
    protected $userRepo;

    public function __construct(NFCeScraperService $scraper, NFCeXMLParserService $xmlParser, UserRepository $userRepo)
    {
        $this->scraper = $scraper;
        $this->xmlParser = $xmlParser;
        $this->userRepo = $userRepo;
    }

    /**
     * Endpoint específico para testar XML direto
     */
    public function processXML(Request $request)
    {
        $request->validate([
            'xml_content' => 'required|string',
        ]);

        $xmlContent = $request->input('xml_content');
        $result = $this->xmlParser->parseXML($xmlContent);

        if ($result['status'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao tentar capturar dados da NFCe',
            ], 400);
        }

        return response([
            'success' => true,
            'message' => 'XML processado com sucesso'
        ]);
    }

    /**
     * Processa o QRCode usando scrapper para obter um JSON com os dados da nota fiscal
     *
     * @param Request $request
     * @return void
     */
    public function processInvoice(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'qr_code_data' => 'required|string',
            'invoice_code' => 'string'
        ]);

        $qrData = $request->input('qr_code_data');

        $result = $this->scraper->scrapeFromQRCode($qrData);

        if ($result['status'] === 'error') {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao tentar capturar dados da NFCe',
                'qr_data' => $qrData
            ], 400);
        }

        $invoice_data = $result['data'];

        $invoice_code = ($request->invoice_code ? 'NFe' . $request->invoice_code : null) ?? $invoice_data['chave_acesso'];

        $company_data = $invoice_data['emitente'];

        //FIRST OR CREATE DE COMPANY
        $company = Company::updateOrCreate(
            [
                'cnpj' => $company_data['cnpj'],
                'ie' => $company_data['ie']
            ],
            [
                'name' => $company_data['razao_social'],
                'cnpj' => $company_data['cnpj'],
                'raw_address' => $company_data['endereco']
                    . ' - ' . $company_data['numero']
                    . ', ' . $company_data['bairro']
                    . ', ' . $company_data['municipio']
                    . ', ' . $company_data['uf'],
                'phone' => $company_data['telefone'],
            ]
        );


        if ($company->wasChanged  || $company->wasRecentlyCreated) {
            $address = Address::firstOrCreate([
                'area' => $company_data['bairro'],
                'city' => $company_data['municipio'],
                'street' => $company_data['endereco'],
                'number' => $company_data['numero'],
            ]);

            $company->address_id = $address->id;
            $company->save();
        }

        //FIRST OR CREATE DE INVOICE
        $invoice = Invoice::firstOrCreate(
            [
                'access_key' => $invoice_code
            ],
            [
                'user_id' => $user->id,
                'receipt_data' => $invoice_data['protocolo']['data_recebimento'] ? DateTime::createFromFormat('d/m/Y H:i:s', $invoice_data['protocolo']['data_recebimento']) : Carbon::today(),
            ]
        );

        if (!($invoice->wasRecentlyCreated)) {
            return response([
                'success' => true,
                'message' => 'NFCe já cadastrada',
            ], 409);
        }

        //INSERE OS PRODUTOS
        //TODO - TRANSFORMAR EM UM JOB DE CRIAÇÃO DE PRODUTOS DEPOIS
        $products_data = $invoice_data['produtos'];
        $unities = Unity::all()->keyBy('abbreviation')->toArray();

        foreach ($products_data as $productData) {

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
                    'unit_id' => $unities[$productData['unidade']]['id'] ?? 1,
                    'quantity' => 1,
                    'average_price' => $productData['valor_unitario']
                ],
            );

            if ($insertedProduct->wasChanged  || $insertedProduct->wasRecentlyCreated) {
                try {
                    CompanyProducts::updateOrCreate(
                        [
                            'product_id' => $insertedProduct->id,
                            'company_id' => $company->id,
                        ],
                        [
                            'average_price' => $productData['valor_unitario']
                        ]
                    );
                } catch (\Throwable $th) {
                    Log::alert([
                        'Error' => 'Impossível cadastrar produto na empresa',
                        'Message' => $th->getMessage()
                    ]);
                }
            }
        }

        //ATRIBUIÇÃO DE PONTOS AO USUÁRIO QUE CRIOU A NOTA
        $this->userRepo->addPoints($user->id, count($products_data));

        return response([
            'success' => true,
            'message' => 'NFCe processada com sucesso'
        ]);
    }
}
