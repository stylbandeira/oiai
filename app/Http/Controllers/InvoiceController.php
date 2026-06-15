<?php

namespace App\Http\Controllers;

use App\Actions\Invoice\ProcessInvoiceAction;
use App\Actions\Invoice\ProcessXMLAction;
use App\Http\Requests\Invoice\ProcessInvoiceRequest;
use App\Http\Requests\Invoice\ProcessXMLRequest;

class InvoiceController extends Controller
{
    /**
     * Endpoint específico para testar XML direto
     */
    public function processXML(ProcessXMLRequest $request, ProcessXMLAction $action)
    {
        return $action->execute($request);
    }

    /**
     * Processa o QRCode usando scrapper para obter um JSON com os dados da nota fiscal
     *
     * @param Request $request
     * @return void
     */
    public function processInvoice(ProcessInvoiceRequest $request, ProcessInvoiceAction $action)
    {
        return $action->execute($request);
    }
}
