<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInvoiceJob;
use App\Models\Address;
use App\Models\Company;
use App\Models\Invoice;
use App\Repositories\UserRepository;
use App\Services\NFCeHtmlParserService;
use App\Services\NFCeScraperService;
use App\Services\NFCeXMLParserService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
        $receipt_data = '';
        $invoice_data = [];

        $request->validate([
            'qr_code_data' => 'string',
            'invoice_code' => 'string'
        ]);

        $qrData = $request->input('qr_code_data');

        if ($request->invoice_code) {
            $response = Http::post('https://dfe-portal.svrs.rs.gov.br/Dfe/ConsultaPublicaDfe', [
                'sistema' => 'Dfe',
                'EhConsultaPublicaSiteSefaz' => True,
                'Ambiente' => 1,
                'ChaveAcessoDfe' => $request->invoice_code
            ]);

            if ($response->successful()) {
                $scraper = new NFCeHtmlParserService();
                $result = $scraper->parse($response);
                $invoice_data = $result;
                $receipt_data = explode('-', $result['dados_nota']['data_emissao'])[0];;
            } else {
                $error = $response->body();
                return response([
                    'error' => $error,
                    'message' => 'Não foi possível verificar a nota fiscal'
                ], 400);
            }
        } else {
            $result = $this->scraper->scrapeFromQRCode($qrData);

            if ($result['status'] === 'error') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao tentar capturar dados da NFCe',
                    'qr_data' => $qrData
                ], 400);
            }
            $invoice_data = $result['data'];
            $receipt_data = $invoice_data['protocolo']['data_recebimento'];
        }


        $invoice_code = ($request->invoice_code ? 'NFe' . $request->invoice_code : null) ?? $invoice_data['chave_acesso'];


        //FIRST OR CREATE DE INVOICE
        $invoice = Invoice::firstOrCreate(
            [
                'access_key' => $invoice_code
            ],
            [
                'user_id' => $user->id,
                'receipt_data' => $receipt_data ? DateTime::createFromFormat('d/m/Y H:i:s', $receipt_data) : Carbon::today(),
                'invoice_data' => json_encode($invoice_data),
                'pending' => true
            ]
        );

        if (!($invoice->wasRecentlyCreated)) {
            return response([
                'success' => true,
                'message' => 'NFCe já cadastrada',
            ], 409);
        }

        return response([
            'success' => true,
            'message' => 'NFCe processada com sucesso'
        ]);
    }
}
