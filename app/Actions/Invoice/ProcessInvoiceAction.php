<?php

namespace App\Actions\Invoice;

use App\Http\Requests\Invoice\ProcessInvoiceRequest;
use App\Models\Invoice;
use App\Services\NFCeHtmlParserService;
use App\Services\NFCeScraperService;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ProcessInvoiceAction
{
    public function __construct(private NFCeScraperService $scraper)
    {
    }

    public function execute(ProcessInvoiceRequest $request)
    {
        $user = Auth::user();
        $receiptData = '';
        $invoiceData = [];

        $qrData = $request->input('qr_code_data');

        if ($request->invoice_code) {
            $client = Http::timeout(120)
                ->connectTimeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml',
                ]);

            $client->get('https://dfe-portal.svrs.rs.gov.br/Dfe/ConsultaPublicaDfe');

            $response = $client
                ->asForm()
                ->withHeaders([
                    'Referer' => 'https://dfe-portal.svrs.rs.gov.br/Dfe/ConsultaPublicaDfe',
                ])
                ->post('https://dfe-portal.svrs.rs.gov.br/Dfe/ConsultaPublicaDfe', [
                    'sistema' => 'Dfe',
                    'EhConsultaPublicaSiteSefaz' => 'True',
                    'Ambiente' => '1',
                    'ChaveAcessoDfe' => $request->invoice_code,
                ]);

            if ($response->successful()) {
                $scraper = new NFCeHtmlParserService();
                $result = $scraper->parse($response);
                $invoiceData = $result;
                $receiptData = explode('-', $result['dados_nota']['data_emissao'])[0];

                if ($result['dados_nota']['modelo'] == '') {
                    $result = $this->scraper->scrapeFromQRCode($request->invoice_code);
                }
            } else {
                return response([
                    'error' => $response->body(),
                    'message' => 'Não foi possível verificar a nota fiscal',
                ], 400);
            }
        } else {
            $result = $this->scraper->scrapeFromQRCode($qrData);

            if ($result['status'] === 'error') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao tentar capturar dados da NFCe',
                    'qr_data' => $qrData,
                ], 400);
            }

            $invoiceData = $result['data'];
            $receiptData = $invoiceData['protocolo']['data_recebimento'];
        }

        $invoiceCode = ($request->invoice_code ? 'NFe' . $request->invoice_code : null) ?? $invoiceData['chave_acesso'];

        $invoice = Invoice::firstOrCreate(
            [
                'access_key' => $invoiceCode,
            ],
            [
                'user_id' => $user->id,
                'receipt_data' => $receiptData ? DateTime::createFromFormat('d/m/Y H:i:s', $receiptData) : Carbon::today(),
                'invoice_data' => json_encode($invoiceData),
                'pending' => true,
            ]
        );

        if (!$invoice->wasRecentlyCreated) {
            return response([
                'success' => true,
                'message' => 'NFCe já cadastrada',
            ], 409);
        }

        return response([
            'success' => true,
            'message' => 'NFCe processada com sucesso',
            'invoice' => $invoice,
        ]);
    }
}
