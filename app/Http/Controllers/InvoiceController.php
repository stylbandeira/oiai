<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\NFCeScraperService;
use App\Services\NFCeXMLParserService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    protected $scraper;
    protected $xmlParser;

    public function __construct(NFCeScraperService $scraper, NFCeXMLParserService $xmlParser)
    {
        $this->scraper = $scraper;
        $this->xmlParser = $xmlParser;
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

        Log::alert($result['data']);

        //TODO - PASSAR DADOS DOS PRODUTOS E ENVIAR CADA DADO PARA CADA
        // SERVICE, POR EXEMPLO: UserService, ProductService, CompanyService.
        // $this->saveToDatabase($result['data']);
        $invoice_code = ($request->invoice_code ? 'NFe' . $request->invoice_code : null) ?? $result['data']['chave_acesso'];

        //FIRST OR CREATE DE COMPANY
        //FIRST OR CREATE DE INVOICE
        //JOB DE CRIAÇÃO DE PRODUTOS +
        //CRIAÇÃO DE TABELA ASSOCIATIVA DE PRODUTOS NA NOTA
        //ASSOCIAÇÃO DE PRODUTOS NA NOTA E NA COMPANY

        $invoice = Invoice::firstOrCreate(
            [
                'access_key' => $invoice_code
            ],
            [
                'user_id' => $user->id,
                'receipt_data' => $result['data']['protocolo']['data_recebimento'] ? DateTime::createFromFormat('d/m/Y H:i:s', $result['data']['protocolo']['data_recebimento']) : Carbon::today(),
            ]
        );

        if ($invoice->wasRecentlyCreated) {
            return response([
                'success' => true,
                'message' => 'NFCe processada com sucesso',
                // 'tipo' => $result['tipo'] ?? 'desconhecido',
                // 'data' => $result['data'],
                // 'produtos_count' => $result['produtos_count'] ?? 0,
                // 'valor_total' => $result['valor_total'] ?? 0,
            ]);
        } else {
            return response([
                'success' => true,
                'message' => 'NFCe já cadastrada',
            ], 409);
        }
    }
}
