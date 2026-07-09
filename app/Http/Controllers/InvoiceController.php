<?php

namespace App\Http\Controllers;

use App\Actions\Invoice\ProcessInvoiceAction;
use App\Actions\Invoice\ProcessXMLAction;
use App\Http\Requests\Invoice\ProcessInvoiceRequest;
use App\Http\Requests\Invoice\ProcessXMLRequest;
use App\Services\Invoice\InvoiceService;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoice_service
    ) {}

    /**
     * Endpoint específico para testar XML direto
     */
    public function processXML(ProcessXMLRequest $request, ProcessXMLAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Cadastra a chave da NFCe para processamento assíncrono.
     */
    public function processInvoice(ProcessInvoiceRequest $request, ProcessInvoiceAction $action)
    {
        $accessKey = $this->invoice_service->extractAccessKey(
            $request->input('invoice_code') ?? $request->input('qr_code_data')
        );

        if (!$accessKey || !$this->invoice_service->isValid($accessKey)) {
            return response([
                'message' => 'Código de NFCe ainda não suportado.'
            ], 400);
        }

        return $action->execute($request);
    }
}
