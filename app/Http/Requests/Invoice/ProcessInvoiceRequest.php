<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProcessInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_code_data' => 'required_without:invoice_code|bail|string',
            'invoice_code' => 'required_without:qr_code_data|bail|string|digits:44',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $invoiceCode = $this->input('invoice_code') ?? $this->input('qr_code_data');

            if (!is_string($invoiceCode) || preg_match('/\d{44}/', $invoiceCode) !== 1) {
                $validator->errors()->add(
                    $this->filled('invoice_code') ? 'invoice_code' : 'qr_code_data',
                    'O código da NFCe deve conter uma chave de acesso com 44 dígitos.'
                );
            }
        });
    }
}
