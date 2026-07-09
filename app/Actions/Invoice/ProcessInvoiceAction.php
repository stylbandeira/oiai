<?php

namespace App\Actions\Invoice;

use App\Http\Requests\Invoice\ProcessInvoiceRequest;
use App\Models\Invoice;
use App\Services\Invoice\InvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProcessInvoiceAction
{
    public function __construct(
        private InvoiceService $invoiceService,
    ) {}

    public function execute(ProcessInvoiceRequest $request)
    {
        $user = Auth::user();
        $accessKey = $this->invoiceService->extractAccessKey(
            $request->input('invoice_code') ?? $request->input('qr_code_data')
        );

        $invoice = Invoice::firstOrCreate(
            [
                'access_key' => $accessKey,
            ],
            [
                'user_id' => $user->id,
                'receipt_data' => Carbon::today(),
                'invoice_data' => null,
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
            'message' => 'NFCe cadastrada para processamento',
            'invoice' => $invoice,
        ]);
    }
}
