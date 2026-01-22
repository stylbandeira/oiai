<?php

namespace App\Http\Controllers;

use App\Services\NFCeScraperService;
use App\Services\NFCeXMLParserService;
use Illuminate\Http\Request;

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
                'error' => $result['error']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'XML processado com sucesso',
            'data' => $result['data']
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
        $request->validate([
            'qr_code_data' => 'required|string',
            'invoice_code' => 'string'
        ]);

        $qrData = $request->input('qr_code_data');

        $result = $this->scraper->scrapeFromQRCode($qrData);

        if ($result['status'] === 'error') {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'qr_data' => $qrData
            ], 400);
        }

        //TODO - PASSAR DADOS DOS PRODUTOS E ENVIAR CADA DADO PARA CADA
        // SERVICE, POR EXEMPLO: UserService, ProductService, CompanyService.
        // $this->saveToDatabase($result['data']);

        return response()->json([
            'success' => true,
            'message' => 'NFCe processada com sucesso',
            // 'tipo' => $result['tipo'] ?? 'desconhecido',
            // 'data' => $result['data'],
            // 'produtos_count' => $result['produtos_count'] ?? 0,
            // 'valor_total' => $result['valor_total'] ?? 0,
        ]);
    }
}
