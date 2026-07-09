<?php

namespace App\Services\Invoice;

use App\Models\Invoice;

class InvoiceService
{
    public function isValid(string $invoiceCode): bool
    {
        return preg_match('/^\d{44}$/', $invoiceCode) === 1
            && $this->areaIsValid($invoiceCode);
    }

    public function extractAccessKey(string $invoiceCode): ?string
    {
        if (preg_match('/\d{44}/', $invoiceCode, $matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    public function areaIsValid(string $invoiceCode): bool
    {
        return in_array(substr($invoiceCode, 0, 2), array_values(Invoice::VALID_AREA_CODES));
    }
}
