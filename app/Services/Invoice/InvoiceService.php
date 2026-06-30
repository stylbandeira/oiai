<?php

namespace App\Services\Invoice;

use App\Models\Invoice;

class InvoiceService
{
    public function isValid(string $invoiceCode): bool
    {
        return $this->areaIsValid($invoiceCode);
    }

    public function areaIsValid(string $invoiceCode): bool
    {
        return in_array(substr($invoiceCode, 0, 2), array_values(Invoice::VALID_AREA_CODES));
    }
}
